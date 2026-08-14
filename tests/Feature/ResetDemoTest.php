<?php

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * El endpoint de reset borra datos, así que la protección es lo primero que se testea.
 */
it('no expone el endpoint si no hay CRON_SECRET configurado', function () {
    config(['demo.cron_secret' => null]);

    $this->getJson(route('cron.reset-demo'))->assertNotFound();
});

it('rechaza un token incorrecto', function () {
    config(['demo.enabled' => true, 'demo.cron_secret' => 'el-secreto-bueno']);

    $this->getJson(route('cron.reset-demo'))->assertForbidden();

    $this->withToken('el-secreto-equivocado')
        ->getJson(route('cron.reset-demo'))
        ->assertForbidden();
});

it('con el token correcto regenera los datos de demo', function () {
    config(['demo.enabled' => true, 'demo.cron_secret' => 'el-secreto-bueno']);

    $this->seed();

    // Alguien entra con las credenciales públicas de admin y rompe la demo:
    // borra todos los servicios y todos los tramos horarios.
    Service::query()->delete();
    Availability::query()->delete();

    expect(Service::count())->toBe(0)
        ->and(Availability::count())->toBe(0);

    $this->withToken('el-secreto-bueno')
        ->getJson(route('cron.reset-demo'))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect(Service::count())->toBeGreaterThan(0)
        ->and(Availability::count())->toBeGreaterThan(0)
        ->and(Booking::count())->toBeGreaterThan(0);
});

it('deja las reservas repartidas alrededor de hoy, no en el pasado', function () {
    // Este es el test que protege contra el envejecimiento: si la demo queda meses
    // publicada, tiene que seguir mostrando turnos próximos.
    config(['demo.enabled' => true]);

    $this->artisan('demo:reset')->assertSuccessful();

    expect(Booking::where('starts_at', '>', now())->count())
        ->toBeGreaterThan(0, 'La demo quedaría sin turnos futuros.');

    expect(Booking::where('starts_at', '<', now())->count())
        ->toBeGreaterThan(0, 'La demo quedaría sin historial.');
});

it('el reset no borra las cuentas de usuario', function () {
    config(['demo.enabled' => true]);

    $this->seed();

    // Alguien se registró mirando la demo: su cuenta no se tiene que perder.
    $visitante = User::factory()->client()->create(['email' => 'visitante@example.com']);

    $this->artisan('demo:reset')->assertSuccessful();

    expect(User::where('email', 'visitante@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'admin@demo.com')->exists())->toBeTrue()
        ->and($visitante->fresh())->not->toBeNull();
});

it('no hace nada si el modo demo está apagado', function () {
    config(['demo.enabled' => false]);

    $this->seed();
    $antes = Booking::count();

    $this->artisan('demo:reset')->assertFailed();

    expect(Booking::count())->toBe($antes);
});
