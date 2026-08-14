<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Reservas de demo repartidas por la agenda.
 *
 * La regla que no se puede romper: dos reservas ACTIVAS (confirmed/completed) no
 * pueden solaparse ni compartir `starts_at`. Hay un índice UNIQUE sobre la columna
 * generada `slot_key` que directamente rechaza el INSERT.
 *
 * En vez de sortear horarios al azar y rezar, se recorre cada franja de atención con
 * un cursor: en cada paso se decide si se ocupa (y el cursor salta la duración del
 * servicio) o si se deja libre (y el cursor avanza 15'). Por construcción, entonces,
 * dos activas nunca pueden pisarse.
 *
 * Las canceladas sí pueden solaparse: su `slot_key` es NULL y el UNIQUE las ignora.
 * Se generan a propósito, porque un hueco que se canceló y se volvió a vender es
 * justo lo que hace interesante la demo.
 */
class BookingSeeder extends Seeder
{
    /** Días hacia atrás desde hoy. */
    private const DIAS_PASADOS = 7;

    /** Días hacia adelante desde hoy. */
    private const DIAS_FUTUROS = 14;

    /**
     * @var array<int, string>
     */
    private const NOTAS = [
        'Prefiere el pelo un poco más largo adelante.',
        'Alergia a productos con amoníaco.',
        'Viene con su hija, sumar 10 minutos.',
        'Cliente nuevo, viene por recomendación.',
        'Pidió el mismo color de la última vez.',
        'Avisar por WhatsApp si se libera un turno antes.',
    ];

    public function run(): void
    {
        /** @var EloquentCollection<int, Service> $servicios */
        $servicios = Service::query()->active()->get();

        /** @var EloquentCollection<int, User> $clientes */
        $clientes = User::query()->where('role', UserRole::Client->value)->get();

        if ($servicios->isEmpty() || $clientes->isEmpty()) {
            return;
        }

        // Re-seedear no debe acumular turnos viejos ni chocar contra el UNIQUE.
        Booking::query()->delete();

        $agenda = Availability::query()->active()->orderBy('start_time')->get()->groupBy('day_of_week');

        $ahora = CarbonImmutable::now();
        $desde = $ahora->startOfDay()->subDays(self::DIAS_PASADOS);
        $duracionMedia = (int) round($servicios->avg('duration_minutes'));

        $total = self::DIAS_PASADOS + self::DIAS_FUTUROS + 1;

        for ($i = 0; $i < $total; $i++) {
            $dia = $desde->addDays($i);

            /** @var EloquentCollection<int, Availability>|null $tramos */
            $tramos = $agenda->get($dia->dayOfWeek);

            // Lunes y domingo no tienen agenda: no hay nada que ocupar.
            if ($tramos === null || $tramos->isEmpty()) {
                continue;
            }

            $this->llenarDia($dia, $tramos, $servicios, $clientes, $ahora, $duracionMedia);
        }
    }

    /**
     * Recorre los tramos del día ocupando huecos con un cursor.
     *
     * @param  EloquentCollection<int, Availability>  $tramos
     * @param  EloquentCollection<int, Service>  $servicios
     * @param  EloquentCollection<int, User>  $clientes
     */
    private function llenarDia(
        CarbonImmutable $dia,
        EloquentCollection $tramos,
        EloquentCollection $servicios,
        EloquentCollection $clientes,
        CarbonImmutable $ahora,
        int $duracionMedia,
    ): void {
        // Ocupación objetivo del día: entre el 30% y el 50%, para que siempre quede
        // hueco libre visible en la grilla.
        $objetivo = fake()->numberBetween(30, 50) / 100;
        $probabilidad = $this->probabilidadPorPaso($objetivo, $duracionMedia);

        foreach ($tramos as $tramo) {
            $finTramo = $dia->setTimeFromTimeString($tramo->end_time);
            $cursor = $dia->setTimeFromTimeString($tramo->start_time);

            while ($cursor->lessThan($finTramo)) {
                $candidatos = $servicios->filter(
                    fn (Service $servicio) => $cursor->addMinutes($servicio->duration_minutes)->lessThanOrEqualTo($finTramo)
                );

                // Ya no entra ni el servicio más corto: el resto del tramo queda libre.
                if ($candidatos->isEmpty()) {
                    break;
                }

                if (! fake()->boolean($probabilidad)) {
                    // Hueco libre. De vez en cuando dejamos una cancelada encima: no
                    // bloquea nada, pero muestra que el turno existió y se dio de baja.
                    if (fake()->boolean(5)) {
                        $this->crear($candidatos->random(), $clientes->random(), $cursor, BookingStatus::Cancelled, $ahora);
                    }

                    $cursor = $cursor->addMinutes(AvailabilityService::SLOT_STEP_MINUTES);

                    continue;
                }

                /** @var Service $servicio */
                $servicio = $candidatos->random();

                // Alguien lo había reservado y lo canceló; después se volvió a vender.
                // Comparten `starts_at` sin romper el UNIQUE porque la cancelada va con
                // `slot_key` en NULL.
                if (fake()->boolean(12)) {
                    $this->crear($candidatos->random(), $clientes->random(), $cursor, BookingStatus::Cancelled, $ahora);
                }

                $this->crear($servicio, $clientes->random(), $cursor, $this->estadoActivo($cursor, $ahora), $ahora);

                $cursor = $cursor->addMinutes($servicio->duration_minutes);
            }
        }
    }

    /**
     * Probabilidad (en %) de ocupar en cada paso para acercarse a la ocupación objetivo.
     *
     * Ocupar consume `$duracionMedia` minutos y no ocupar consume el paso de 15', así
     * que la fracción de agenda ocupada tiende a  p·d / (p·d + (1−p)·s).  Despejando p
     * para una ocupación `t` queda la fórmula de abajo.
     */
    private function probabilidadPorPaso(float $objetivo, int $duracionMedia): int
    {
        $paso = AvailabilityService::SLOT_STEP_MINUTES;

        $p = ($objetivo * $paso) / ($duracionMedia * (1 - $objetivo) + $objetivo * $paso);

        return (int) round($p * 100);
    }

    /**
     * Los turnos que ya pasaron se dan por atendidos; los que vienen, confirmados.
     */
    private function estadoActivo(CarbonImmutable $inicio, CarbonImmutable $ahora): BookingStatus
    {
        return $inicio->lessThan($ahora) ? BookingStatus::Completed : BookingStatus::Confirmed;
    }

    private function crear(
        Service $servicio,
        User $cliente,
        CarbonImmutable $inicio,
        BookingStatus $estado,
        CarbonImmutable $ahora,
    ): void {
        // La reserva se cargó antes de la fecha del turno, nunca en el futuro.
        $creada = $inicio->subDays(fake()->numberBetween(1, 12))->setTime(
            fake()->numberBetween(9, 21),
            fake()->numberBetween(0, 59),
        );

        if ($creada->greaterThan($ahora)) {
            $creada = $ahora;
        }

        Booking::query()->create([
            'service_id' => $servicio->id,
            'user_id' => $cliente->id,
            'starts_at' => $inicio,
            'ends_at' => $inicio->addMinutes($servicio->duration_minutes),
            'status' => $estado,
            'notes' => fake()->boolean(20) ? fake()->randomElement(self::NOTAS) : null,
            'created_at' => $creada,
            'updated_at' => $creada,
        ]);
    }
}
