<?php

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('no permite reservar dos veces el mismo turno', function () {
    [$service, $startsAt] = bookableSlot();

    $primero = User::factory()->create();
    $segundo = User::factory()->create();

    $bookings = app(BookingService::class);

    $bookings->book($primero, $service, $startsAt);

    expect(fn () => $bookings->book($segundo, $service, $startsAt))
        ->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(1)
        ->and(Booking::first()->user_id)->toBe($primero->id);
});

it('tampoco permite un turno que se solapa parcialmente con otro', function () {
    // El primero ocupa 10:00–11:00. El segundo quiere 10:30–11:00: arranca en otro
    // minuto, así que el índice único NO lo detecta. Lo tiene que frenar la
    // revalidación dentro de la transacción, que compara intervalos completos.
    [$servicioLargo, $startsAt] = bookableSlot(durationMinutes: 60, hour: 10);

    $servicioCorto = Service::factory()->create([
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $bookings = app(BookingService::class);

    $bookings->book(User::factory()->create(), $servicioLargo, $startsAt);

    expect(fn () => $bookings->book(
        User::factory()->create(),
        $servicioCorto,
        $startsAt->addMinutes(30),
    ))->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(1);
});

it('rechaza el duplicado en la base de datos aunque se saltee la capa de servicio', function () {
    // Este test se salta BookingService a propósito e inserta directo con Eloquent.
    // Es la prueba de que el índice único `bookings_active_slot_unique` protege por
    // sí solo: si mañana alguien escribe un comando o un seeder que inserta sin
    // pasar por la transacción, la base sigue diciendo que no.
    [$service, $startsAt] = bookableSlot();

    $fila = fn () => [
        'service_id' => $service->id,
        'user_id' => User::factory()->create()->id,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->addMinutes($service->duration_minutes),
        'status' => BookingStatus::Confirmed,
    ];

    Booking::create($fila());

    expect(fn () => Booking::create($fila()))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(Booking::count())->toBe(1);
});

it('libera el horario cuando la reserva se cancela', function () {
    // Al cancelar, MySQL recalcula la columna generada `slot_key` a NULL y deja de
    // contar para el índice único. El hueco vuelve a estar disponible sin que haya
    // que borrar la fila ni perder el histórico.
    [$service, $startsAt] = bookableSlot();

    $bookings = app(BookingService::class);

    $primera = $bookings->book(User::factory()->create(), $service, $startsAt);
    $bookings->cancel($primera);

    $segunda = $bookings->book(User::factory()->create(), $service, $startsAt);

    expect(Booking::count())->toBe(2)
        ->and($primera->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and($segunda->status)->toBe(BookingStatus::Confirmed);
});

it('permite reservar horarios contiguos que apenas se tocan', function () {
    // 10:00–10:30 y 10:30–11:00 no se solapan: el intervalo es medio abierto.
    // Si este test falla, la comparación de solapamiento se pasó de estricta y el
    // negocio estaría perdiendo turnos vendibles.
    [$service, $startsAt] = bookableSlot(durationMinutes: 30, hour: 10);

    $bookings = app(BookingService::class);

    $bookings->book(User::factory()->create(), $service, $startsAt);
    $bookings->book(User::factory()->create(), $service, $startsAt->addMinutes(30));

    expect(Booking::count())->toBe(2);
});

it('no permite reservar fuera del horario de atención', function () {
    // La agenda del helper es 09:00–18:00. Las 21:00 no existe como hueco.
    [$service, $startsAt] = bookableSlot(hour: 21);

    expect(fn () => app(BookingService::class)->book(
        User::factory()->create(),
        $service,
        $startsAt,
    ))->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(0);
});
