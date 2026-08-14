<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * El acceso de un clic desde el login es un requisito del proyecto: quien abra la
 * demo tiene que poder ver las dos caras del sistema sin registrarse. Estos tests
 * lo cubren de punta a punta para que no se rompa en silencio.
 */
it('comparte las credenciales demo con el frontend cuando el modo demo está activo', function () {
    config(['demo.enabled' => true]);

    $props = $this->get(route('login'))->assertOk()->viewData('page')['props'];

    expect($props['demo'])->toHaveCount(2);

    $emails = array_column($props['demo'], 'email');

    expect($emails)->toContain('admin@demo.com', 'cliente@demo.com');
});

it('no expone las credenciales demo si el modo demo está apagado', function () {
    config(['demo.enabled' => false]);

    $props = $this->get(route('login'))->assertOk()->viewData('page')['props'];

    expect($props['demo'])->toBeNull();
});

it('las credenciales demo que se muestran son las que realmente funcionan', function () {
    // Este es el test que importa: que lo que dice la pantalla y lo que crea el
    // seeder no se separen. Si alguien cambia la contraseña en el seeder y no en la
    // config (o al revés), esto falla.
    $this->seed(UserSeeder::class);

    foreach (config('demo.accounts') as $account) {
        $this->post(route('login.store'), [
            'email' => $account['email'],
            'password' => $account['password'],
        ])->assertRedirect();

        expect(auth()->check())->toBeTrue("No se pudo entrar con {$account['email']}.");

        auth()->logout();
    }
});

it('cada cuenta demo tiene el rol que promete', function () {
    $this->seed(UserSeeder::class);

    $admin = User::where('email', 'admin@demo.com')->firstOrFail();
    $cliente = User::where('email', 'cliente@demo.com')->firstOrFail();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->isAdmin())->toBeTrue()
        ->and($cliente->role)->toBe(UserRole::Client)
        ->and($cliente->isAdmin())->toBeFalse();

    // Y ninguno queda trabado en la verificación de email.
    expect($admin->email_verified_at)->not->toBeNull()
        ->and($cliente->email_verified_at)->not->toBeNull();
});
