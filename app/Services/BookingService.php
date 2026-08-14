<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creación y cambios de estado de reservas.
 *
 * Todo el archivo gira alrededor de un solo problema: que dos personas pulsando
 * "Reservar" en el mismo segundo no puedan quedarse las dos con el turno de las 10:00.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *  CÓMO **NO** SE HACE  (esto es exactamente el bug que este proyecto evita)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *   public function book(User $user, Service $service, CarbonImmutable $startsAt): Booking
 *   {
 *       // (1) Consulto si está libre...
 *       $ocupado = Booking::query()
 *           ->blocking()
 *           ->overlapping($startsAt, $startsAt->addMinutes($service->duration_minutes))
 *           ->exists();
 *
 *       if ($ocupado) {
 *           throw new SlotUnavailableException();
 *       }
 *
 *       // (2) ...y después inserto.
 *       return Booking::create([...]);
 *   }
 *
 * Se ve inofensivo y pasa todos los tests secuenciales. Pero entre (1) y (2) hay una
 * ventana de unos pocos milisegundos en la que NADA protege el turno. Dos requests
 * concurrentes ejecutan (1) a la vez, las dos leen "libre", las dos siguen a (2), y el
 * negocio termina con dos clientes sentados en la misma silla a las 10:00.
 *
 * Es el clásico TOCTOU (time-of-check to time-of-use). No se arregla validando "mejor"
 * ni moviendo la validación de sitio: mientras la comprobación y la escritura sean dos
 * operaciones separadas sin un candado en el medio, la ventana existe.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 *  CÓMO SÍ SE HACE  (ver book() más abajo)
 * ─────────────────────────────────────────────────────────────────────────────
 *
 *   1. Abrir una transacción.
 *   2. SELECT ... FOR UPDATE sobre las reservas del día → la segunda transacción
 *      queda esperando en el candado en vez de leer datos obsoletos.
 *   3. Revalidar la disponibilidad DENTRO de la transacción, con las filas bloqueadas.
 *   4. Insertar y hacer commit → recién ahí se suelta el candado.
 *   5. Y si aun así algo se escapara, el índice único de MySQL rechaza el INSERT.
 */
class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Reserva un turno de forma segura frente a concurrencia.
     *
     * @throws SlotUnavailableException
     */
    public function book(User $user, Service $service, CarbonImmutable $startsAt, ?string $notes = null): Booking
    {
        $endsAt = $startsAt->addMinutes($service->duration_minutes);

        // El segundo argumento son los reintentos: si InnoDB detecta un deadlock entre
        // dos transacciones y mata una, Laravel la vuelve a ejecutar desde cero.
        $booking = DB::transaction(function () use ($user, $service, $startsAt, $endsAt, $notes): Booking {
            // ── PASO 1: bloquear ────────────────────────────────────────────────
            //
            // FOR UPDATE sobre el rango del día. Dos detalles que importan:
            //
            //  · Sobre las filas que existen toma locks exclusivos: nadie más las lee
            //    con FOR UPDATE ni las modifica hasta el commit.
            //
            //  · Sobre los huecos entre filas toma gap locks (next-key locking de
            //    InnoDB en REPEATABLE READ). Esto es lo que hace que el caso "todavía
            //    no hay ninguna reserva ese día" también quede protegido: sin gap
            //    locks, dos transacciones leerían cero filas y ambas insertarían.
            //
            // El índice (status, starts_at) es el que sostiene este bloqueo; por eso
            // está declarado en la migración y no es decorativo.
            $lockedBookings = Booking::query()
                ->blocking()
                ->whereBetween('starts_at', [$startsAt->startOfDay(), $startsAt->endOfDay()])
                ->orderBy('starts_at')
                ->lockForUpdate()
                ->get(['id', 'starts_at', 'ends_at', 'status']);

            // ── PASO 2: revalidar YA bloqueado ──────────────────────────────────
            //
            // Le pasamos las filas que acabamos de bloquear en vez de dejar que
            // AvailabilityService las vuelva a consultar: reutilizar el resultado del
            // SELECT ... FOR UPDATE es lo que garantiza que estamos decidiendo sobre
            // el mismo estado que tenemos bloqueado.
            //
            // La comprobación que hizo el navegador al pintar la grilla no cuenta:
            // pudo haber pasado un minuto entero desde entonces.
            if (! $this->availability->isBookableSlot($service, $startsAt, $lockedBookings)) {
                throw new SlotUnavailableException;
            }

            // ── PASO 3: insertar ────────────────────────────────────────────────
            try {
                return Booking::create([
                    'service_id' => $service->id,
                    'user_id' => $user->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => BookingStatus::Confirmed,
                    'notes' => $notes,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // ── PASO 4: red de seguridad ────────────────────────────────────
                //
                // Si llegamos acá, el bloqueo falló (índice caído, alguien tocando la
                // base a mano, un futuro refactor que rompa la transacción) y el índice
                // `bookings_active_slot_unique` hizo su trabajo. Preferimos un error
                // controlado antes que un turno duplicado.
                throw new SlotUnavailableException(previous: $e);
            }
        }, attempts: 3);

        SendBookingConfirmation::dispatch($booking);

        return $booking;
    }

    /**
     * Cancela una reserva y libera el hueco.
     *
     * Al pasar a `cancelled`, MySQL recalcula la columna generada `slot_key` a NULL y
     * el turno vuelve a estar disponible para cualquiera, sin borrar el histórico.
     */
    public function cancel(Booking $booking): Booking
    {
        $booking->update(['status' => BookingStatus::Cancelled]);

        return $booking;
    }

    /**
     * Cambia el estado de una reserva (panel de admin).
     *
     * Reactivar una reserva cancelada vuelve a ocupar el turno, así que pasa por el
     * mismo camino protegido que book(): candado, revalidación y índice único.
     *
     * @throws SlotUnavailableException
     */
    public function changeStatus(Booking $booking, BookingStatus $status): Booking
    {
        $wasBlocking = in_array($booking->status->value, BookingStatus::blocking(), true);
        $willBlock = in_array($status->value, BookingStatus::blocking(), true);

        // Cancelar, o moverse entre dos estados que ya ocupaban, no puede generar
        // conflictos: no hay turno nuevo que disputar.
        if (! $willBlock || $wasBlocking) {
            $booking->update(['status' => $status]);

            return $booking;
        }

        return DB::transaction(function () use ($booking, $status): Booking {
            $lockedBookings = Booking::query()
                ->blocking()
                ->whereBetween('starts_at', [$booking->starts_at->copy()->startOfDay(), $booking->starts_at->copy()->endOfDay()])
                ->whereKeyNot($booking->getKey())
                ->lockForUpdate()
                ->get(['id', 'starts_at', 'ends_at', 'status']);

            if ($this->overlapsAny($booking, $lockedBookings)) {
                throw new SlotUnavailableException('No se puede reactivar: el horario ya está ocupado por otra reserva.');
            }

            try {
                $booking->update(['status' => $status]);
            } catch (UniqueConstraintViolationException $e) {
                throw new SlotUnavailableException('No se puede reactivar: el horario ya está ocupado por otra reserva.', $e);
            }

            return $booking;
        }, attempts: 3);
    }

    /**
     * @param  EloquentCollection<int, Booking>  $others
     */
    private function overlapsAny(Booking $booking, EloquentCollection $others): bool
    {
        foreach ($others as $other) {
            if ($other->starts_at->lessThan($booking->ends_at) && $other->ends_at->greaterThan($booking->starts_at)) {
                return true;
            }
        }

        return false;
    }
}
