<?php

namespace App\Console\Commands;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Devuelve la demo a su estado inicial.
 *
 * Existe por dos razones concretas, las dos propias de una demo pública que va a
 * quedar viva durante meses:
 *
 *  1. Los datos envejecen. BookingSeeder reparte turnos de -7 a +14 días desde el
 *     momento en que corre. Sin refrescarlos, a las tres semanas la agenda del día
 *     está vacía, "Mis reservas" no tiene próximos turnos y el sistema parece muerto
 *     justo cuando alguien lo abre por primera vez.
 *
 *  2. Las credenciales de administrador son públicas y están escritas en el login.
 *     Cualquiera puede entrar y borrar los servicios o los tramos horarios, y sin
 *     tramos la grilla queda vacía para todo el mundo, para siempre. Un reset diario
 *     hace que el peor destrozo posible dure unas horas.
 *
 * NO usa migrate:fresh a propósito: la base vive en un servicio MySQL compartido con
 * otros proyectos y el usuario tiene permisos sobre todo el servidor. Acá se borran
 * filas de tres tablas concretas y nada más.
 */
class ResetDemoCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Borra los datos de demostración y los vuelve a generar frescos.';

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->components->error('El modo demo está apagado (DEMO_MODE). No se hace nada.');

            return self::FAILURE;
        }

        $this->components->info('Base: '.DB::connection()->getDatabaseName());

        // El orden importa por las claves foráneas: primero lo que referencia.
        // Los usuarios NO se tocan: los seeders los reconcilian con updateOrCreate y
        // así una cuenta que alguien haya creado mirando la demo no se pierde.
        Booking::query()->delete();
        Service::query()->delete();
        Availability::query()->delete();

        $this->call('db:seed', ['--force' => true]);

        $this->components->info(sprintf(
            'Demo reiniciada: %d servicios, %d tramos, %d reservas.',
            Service::query()->count(),
            Availability::query()->count(),
            Booking::query()->count(),
        ));

        return self::SUCCESS;
    }
}
