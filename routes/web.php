<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\BookingStatusController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ResetDemoController;
use App\Http\Controllers\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Público
|--------------------------------------------------------------------------
*/

Route::get('/', [ServiceCatalogController::class, 'index'])->name('home');

// El selector de horarios es público: cualquiera puede mirar la disponibilidad real.
// Lo que exige cuenta es confirmar la reserva (POST más abajo).
Route::get('reservar/{service:slug}', [BookingController::class, 'create'])->name('bookings.create');

/*
|--------------------------------------------------------------------------
| Cliente autenticado
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Tras el login, cada rol aterriza donde le sirve. Mantenemos el nombre
    // `dashboard` porque es al que redirige Fortify y el que usan los componentes
    // del starter kit.
    Route::get('dashboard', function () {
        return request()->user()->isAdmin()
            ? to_route('admin.agenda')
            : to_route('bookings.index');
    })->name('dashboard');

    Route::post('reservas', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('mis-reservas', [BookingController::class, 'index'])->name('bookings.index');
    Route::delete('reservas/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
});

/*
|--------------------------------------------------------------------------
| Administración
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AgendaController::class, 'index'])->name('agenda');

        Route::get('servicios', [ServiceController::class, 'index'])->name('services.index');
        Route::post('servicios', [ServiceController::class, 'store'])->name('services.store');
        Route::put('servicios/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('servicios/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('disponibilidad', [AvailabilityController::class, 'index'])->name('availability.index');
        Route::post('disponibilidad', [AvailabilityController::class, 'store'])->name('availability.store');
        Route::put('disponibilidad/{availability}', [AvailabilityController::class, 'update'])->name('availability.update');
        Route::delete('disponibilidad/{availability}', [AvailabilityController::class, 'destroy'])->name('availability.destroy');

        Route::patch('reservas/{booking}/estado', [BookingStatusController::class, 'update'])->name('bookings.status');
    });

/*
|--------------------------------------------------------------------------
| Mantenimiento de la demo
|--------------------------------------------------------------------------
|
| Lo llama el cron de Vercel una vez por día (ver la clave `crons` en vercel.json).
| Está protegido por CRON_SECRET; sin ese header devuelve 403.
|
*/

Route::get('cron/reset-demo', ResetDemoController::class)->name('cron.reset-demo');

require __DIR__.'/settings.php';
