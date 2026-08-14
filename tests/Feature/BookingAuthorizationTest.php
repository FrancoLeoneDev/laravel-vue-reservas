<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('un cliente sólo ve sus propias reservas', function () {
    [$service, $startsAt] = bookableSlot();

    $propia = User::factory()->client()->create();
    $ajena = User::factory()->client()->create();

    $bookings = app(BookingService::class);
    $bookings->book($propia, $service, $startsAt);
    $bookings->book($ajena, $service, $startsAt->addHour());

    $response = $this->actingAs($propia)->get(route('bookings.index'));

    $response->assertOk();

    // La página recibe sólo una reserva: la del usuario logueado.
    $upcoming = $response->viewData('page')['props']['upcoming'];

    expect($upcoming)->toHaveCount(1)
        ->and(Booking::count())->toBe(2);
});

it('un cliente no puede cancelar la reserva de otro', function () {
    [$service, $startsAt] = bookableSlot();

    $dueno = User::factory()->client()->create();
    $intruso = User::factory()->client()->create();

    $reserva = app(BookingService::class)->book($dueno, $service, $startsAt);

    $this->actingAs($intruso)
        ->delete(route('bookings.destroy', $reserva))
        ->assertForbidden();

    expect($reserva->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('un cliente sí puede cancelar la suya', function () {
    [$service, $startsAt] = bookableSlot();

    $dueno = User::factory()->client()->create();
    $reserva = app(BookingService::class)->book($dueno, $service, $startsAt);

    $this->actingAs($dueno)
        ->delete(route('bookings.destroy', $reserva))
        ->assertRedirect();

    expect($reserva->fresh()->status)->toBe(BookingStatus::Cancelled);
});

it('un cliente no entra al panel de administración', function () {
    $cliente = User::factory()->client()->create();

    $this->actingAs($cliente)->get(route('admin.agenda'))->assertForbidden();
    $this->actingAs($cliente)->get(route('admin.services.index'))->assertForbidden();
    $this->actingAs($cliente)->get(route('admin.availability.index'))->assertForbidden();
});

it('todas las pantallas del panel responden con datos reales', function () {
    // Humo sobre los tres controladores del admin con la base ya poblada: confirma
    // que las props que arma el backend se construyen sin reventar. Si algún día
    // alguien renombra una columna, esto se cae acá y no en producción.
    $this->seed();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.agenda'))->assertOk();
    $this->actingAs($admin)->get(route('admin.agenda', ['view' => 'week']))->assertOk();
    $this->actingAs($admin)->get(route('admin.services.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.availability.index'))->assertOk();

    // Y las pantallas públicas con catálogo cargado.
    $this->get(route('home'))->assertOk();

    $servicio = Service::query()->where('is_active', true)->firstOrFail();
    $this->get(route('bookings.create', $servicio))->assertOk();
});

it('el admin entra al panel y puede cambiar el estado de cualquier reserva', function () {
    [$service, $startsAt] = bookableSlot();

    $admin = User::factory()->admin()->create();
    $cliente = User::factory()->client()->create();

    $reserva = app(BookingService::class)->book($cliente, $service, $startsAt);

    $this->actingAs($admin)->get(route('admin.agenda'))->assertOk();

    $this->actingAs($admin)
        ->patch(route('admin.bookings.status', $reserva), ['status' => BookingStatus::Completed->value])
        ->assertRedirect();

    expect($reserva->fresh()->status)->toBe(BookingStatus::Completed);
});

it('un invitado puede mirar la disponibilidad pero no reservar', function () {
    [$service, $startsAt] = bookableSlot();

    // Mirar los huecos, sí: es a propósito que no haga falta cuenta para eso.
    $this->get(route('bookings.create', $service))->assertOk();

    // Reservar, no.
    $this->post(route('bookings.store'), [
        'service_id' => $service->id,
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
    ])->assertRedirect(route('login'));

    expect(Booking::count())->toBe(0);
});
