<?php

use App\Models\Availability;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Caso base
|--------------------------------------------------------------------------
|
| A propósito NO se aplica RefreshDatabase de forma global: el test de
| concurrencia necesita que los datos estén realmente commiteados para que una
| segunda conexión pueda verlos, así que cada archivo declara el trait que le
| corresponde.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers de dominio
|--------------------------------------------------------------------------
*/

/**
 * Arma un escenario reservable: un servicio, la agenda del negocio para ese día
 * y un horario concreto que cae dentro de la franja de atención.
 *
 * Devuelve `[Service, CarbonImmutable $startsAt]`.
 *
 * @return array{0: Service, 1: CarbonImmutable}
 */
function bookableSlot(int $durationMinutes = 30, int $hour = 10): array
{
    // Dos días para adelante, así nunca choca con "el turno ya pasó" ni depende
    // de la hora a la que se corran los tests.
    $date = CarbonImmutable::today()->addDays(2);

    $service = Service::factory()->create([
        'duration_minutes' => $durationMinutes,
        'is_active' => true,
    ]);

    Availability::factory()->create([
        'day_of_week' => $date->dayOfWeek,
        'start_time' => '09:00:00',
        'end_time' => '18:00:00',
        'is_active' => true,
    ]);

    return [$service, $date->setTime($hour, 0)];
}
