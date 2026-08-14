<?php

namespace Database\Seeders;

use App\Models\Availability;
use Illuminate\Database\Seeder;

/**
 * Agenda semanal del local.
 *
 * Lunes y domingo no tienen filas: un día sin tramos activos simplemente no ofrece
 * huecos, no hace falta marcarlo como "cerrado" en ningún lado.
 *
 * Recordatorio: day_of_week va 0 = domingo ... 6 = sábado (criterio Carbon::dayOfWeek).
 */
class AvailabilitySeeder extends Seeder
{
    /**
     * @var array<int, array{int, string, string}>
     */
    private const TRAMOS = [
        // Martes a viernes: mañana y tarde, con el corte del mediodía.
        [2, '09:00:00', '13:00:00'],
        [2, '15:00:00', '20:00:00'],
        [3, '09:00:00', '13:00:00'],
        [3, '15:00:00', '20:00:00'],
        [4, '09:00:00', '13:00:00'],
        [4, '15:00:00', '20:00:00'],
        [5, '09:00:00', '13:00:00'],
        [5, '15:00:00', '20:00:00'],

        // Sábado: corrido hasta el mediodía largo.
        [6, '09:00:00', '14:00:00'],
    ];

    public function run(): void
    {
        foreach (self::TRAMOS as [$dayOfWeek, $startTime, $endTime]) {
            Availability::query()->updateOrCreate(
                [
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                ],
                [
                    'end_time' => $endTime,
                    'is_active' => true,
                ],
            );
        }
    }
}
