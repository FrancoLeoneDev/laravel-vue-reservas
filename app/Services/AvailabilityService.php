<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Calcula los huecos reservables.
 *
 * No existe una tabla de slots precargados: los huecos se derivan en cada consulta
 * a partir de tres cosas:
 *
 *   agenda del negocio (Availability)  −  reservas que ya ocupan  ⊗  duración del servicio
 *
 * Esto es a propósito. Una tabla de slots obligaría a regenerar filas cada vez que
 * cambia un horario o la duración de un servicio, y dejaría huérfanos los turnos ya
 * vendidos. Calculándolo, cambiar la agenda es un UPDATE y nada más.
 */
class AvailabilityService
{
    /**
     * Cada cuántos minutos puede arrancar un turno dentro de la franja de atención.
     *
     * Con paso de 15' y un servicio de 45', los inicios posibles son 09:00, 09:15,
     * 09:30... y no sólo 09:00 / 09:45. Da una grilla más densa sin fragmentar la agenda.
     */
    public const SLOT_STEP_MINUTES = 15;

    /**
     * Tramos de atención agrupados por día de la semana, cargados una sola vez.
     *
     * Sin esto, dailySummary() consultaría la tabla `availabilities` una vez por cada
     * día del rango: 14 consultas idénticas para pintar un selector de fechas. Es el
     * mismo N+1 de siempre, sólo que escondido detrás de un bucle en vez de una
     * relación de Eloquent.
     *
     * @var Collection<int, Collection<int, Availability>>|null
     */
    private ?Collection $windowsByDay = null;

    /**
     * Huecos reservables para un servicio en una fecha concreta.
     *
     * @param  EloquentCollection<int, Booking>|null  $existingBookings  Reservas ya cargadas.
     *                                                                   Se inyectan desde BookingService para reutilizar
     *                                                                   las filas ya bloqueadas por el SELECT ... FOR UPDATE
     *                                                                   en vez de volver a consultarlas (y romper el lock).
     * @return array<int, array{starts_at: string, ends_at: string, label: string}>
     */
    public function availableSlots(Service $service, CarbonImmutable $date, ?EloquentCollection $existingBookings = null): array
    {
        $date = $date->startOfDay();

        $windows = $this->windowsFor($date);

        if ($windows->isEmpty()) {
            return [];
        }

        $bookings = $existingBookings ?? $this->blockingBookingsOn($date);
        $now = CarbonImmutable::now();

        $slots = [];

        foreach ($windows as $window) {
            $windowStart = $date->setTimeFromTimeString($window->start_time);
            $windowEnd = $date->setTimeFromTimeString($window->end_time);

            for (
                $start = $windowStart;
                $start->addMinutes($service->duration_minutes)->lessThanOrEqualTo($windowEnd);
                $start = $start->addMinutes(self::SLOT_STEP_MINUTES)
            ) {
                $end = $start->addMinutes($service->duration_minutes);

                // Un turno que ya empezó no se puede reservar.
                if ($start->lessThanOrEqualTo($now)) {
                    continue;
                }

                if ($this->overlapsAny($start, $end, $bookings)) {
                    continue;
                }

                $slots[] = [
                    'starts_at' => $start->format('Y-m-d H:i:s'),
                    'ends_at' => $end->format('Y-m-d H:i:s'),
                    'label' => $start->format('H:i'),
                ];
            }
        }

        return $slots;
    }

    /**
     * ¿Es `$startsAt` un inicio de turno válido y libre para este servicio?
     *
     * Deliberadamente se apoya en availableSlots() en lugar de repetir la lógica:
     * lo que la UI muestra y lo que el backend acepta salen de la misma función, así
     * que no pueden divergir. Es el chequeo que BookingService corre DENTRO de la
     * transacción, ya con las filas bloqueadas.
     *
     * @param  EloquentCollection<int, Booking>|null  $existingBookings
     */
    public function isBookableSlot(Service $service, CarbonImmutable $startsAt, ?EloquentCollection $existingBookings = null): bool
    {
        $needle = $startsAt->format('Y-m-d H:i:s');

        foreach ($this->availableSlots($service, $startsAt, $existingBookings) as $slot) {
            if ($slot['starts_at'] === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resumen de los próximos días: cuántos huecos quedan en cada uno.
     *
     * Sirve para pintar el selector de fecha sin pedir un endpoint por día. Carga las
     * reservas de todo el rango de una sola consulta y las agrupa en memoria, y la
     * agenda queda cacheada en `$windowsByDay`: son 2 consultas en total, sin importar
     * cuántos días se pidan.
     *
     * @return array<int, array{date: string, label: string, weekday: string, slots: int}>
     */
    public function dailySummary(Service $service, CarbonImmutable $from, int $days = 14): array
    {
        $from = $from->startOfDay();
        $to = $from->addDays($days - 1)->endOfDay();

        $bookingsByDay = $this->blockingBookingsBetween($from, $to)
            ->groupBy(fn (Booking $booking) => $booking->starts_at->format('Y-m-d'));

        $summary = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $from->addDays($i);
            $key = $date->format('Y-m-d');

            /** @var EloquentCollection<int, Booking> $dayBookings */
            $dayBookings = $bookingsByDay->get($key) ?? new EloquentCollection;

            $summary[] = [
                'date' => $key,
                'label' => $date->translatedFormat('d M'),
                'weekday' => Availability::DAY_NAMES[$date->dayOfWeek],
                'slots' => count($this->availableSlots($service, $date, $dayBookings)),
            ];
        }

        return $summary;
    }

    /**
     * Tramos de atención activos para el día de la semana de `$date`.
     *
     * @return Collection<int, Availability>
     */
    private function windowsFor(CarbonImmutable $date): Collection
    {
        $this->windowsByDay ??= Availability::query()
            ->active()
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return $this->windowsByDay->get($date->dayOfWeek) ?? new Collection;
    }

    /**
     * @return EloquentCollection<int, Booking>
     */
    private function blockingBookingsOn(CarbonImmutable $date): EloquentCollection
    {
        return $this->blockingBookingsBetween($date->startOfDay(), $date->endOfDay());
    }

    /**
     * @return EloquentCollection<int, Booking>
     */
    private function blockingBookingsBetween(CarbonImmutable $from, CarbonImmutable $to): EloquentCollection
    {
        return Booking::query()
            ->blocking()
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get(['id', 'starts_at', 'ends_at', 'status']);
    }

    /**
     * Dos intervalos medio abiertos [a, b) se solapan si a1 < b2 && b1 > a2.
     *
     * Que se toquen los extremos no es solaparse: un turno que termina 10:30 y otro
     * que arranca 10:30 conviven perfectamente.
     *
     * @param  EloquentCollection<int, Booking>  $bookings
     */
    private function overlapsAny(CarbonImmutable $start, CarbonImmutable $end, EloquentCollection $bookings): bool
    {
        foreach ($bookings as $booking) {
            if ($booking->starts_at->lessThan($end) && $booking->ends_at->greaterThan($start)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza a CarbonImmutable en la zona horaria de la app.
     */
    public static function parse(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, Carbon::now()->timezone);
    }
}
