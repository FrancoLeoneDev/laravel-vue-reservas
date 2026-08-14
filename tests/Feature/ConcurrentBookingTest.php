<?php

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Concurrencia real
|--------------------------------------------------------------------------
|
| DatabaseMigrations en vez de RefreshDatabase, y no es un detalle: RefreshDatabase
| envuelve cada test en una transacción que nunca se commitea, así que una segunda
| conexión no vería NADA de lo que hizo la primera y el test no probaría nada.
|
| Acá hacen falta datos realmente commiteados y dos conexiones físicas distintas
| contra el mismo MySQL, que es lo más parecido a dos personas pulsando "Reservar"
| en el mismo segundo que se puede montar dentro de un test.
|
*/

uses(DatabaseMigrations::class);

/**
 * Abre una segunda conexión física a la misma base, con un timeout de candado corto
 * para que el test sea rápido y determinista en vez de esperar los 50 segundos que
 * InnoDB usa por defecto.
 */
function rivalConnection(): Connection
{
    config([
        'database.connections.rival' => config('database.connections.'.config('database.default')),
    ]);

    DB::purge('rival');

    $rival = DB::connection('rival');
    $rival->statement('SET SESSION innodb_lock_wait_timeout = 2');

    return $rival;
}

/**
 * @return array<string, mixed>
 */
function bookingRow(int $serviceId, int $userId, CarbonImmutable $startsAt, int $minutes): array
{
    return [
        'service_id' => $serviceId,
        'user_id' => $userId,
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $startsAt->addMinutes($minutes)->format('Y-m-d H:i:s'),
        'status' => BookingStatus::Confirmed->value,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

it('con dos reservas concurrentes sobre el mismo turno, sólo una sobrevive', function () {
    [$service, $startsAt] = bookableSlot();

    $ana = User::factory()->create();
    $beto = User::factory()->create();

    $principal = DB::connection();
    $rival = rivalConnection();

    // ── Ana: abre la transacción, toma el candado y escribe, sin commitear todavía.
    $principal->beginTransaction();

    $principal->table('bookings')
        ->whereIn('status', BookingStatus::blocking())
        ->whereBetween('starts_at', [$startsAt->startOfDay(), $startsAt->endOfDay()])
        ->lockForUpdate()
        ->get();

    $principal->table('bookings')->insert(
        bookingRow($service->id, $ana->id, $startsAt, $service->duration_minutes)
    );

    // ── Beto, desde otra conexión, intenta exactamente el mismo horario.
    //    Queda esperando el candado de Ana y muere por timeout.
    $betoQuedoBloqueado = false;

    try {
        $rival->table('bookings')->insert(
            bookingRow($service->id, $beto->id, $startsAt, $service->duration_minutes)
        );
    } catch (QueryException) {
        $betoQuedoBloqueado = true;
    }

    $principal->commit();

    expect($betoQuedoBloqueado)->toBeTrue('La segunda conexión debería haber quedado bloqueada.');
    expect(Booking::count())->toBe(1);
    expect(Booking::first()->user_id)->toBe($ana->id);

    // ── Ya con la transacción de Ana cerrada, Beto reintenta por el camino normal.
    //    Ahora la revalidación ve el turno tomado y devuelve un error legible en vez
    //    de un choque de base de datos.
    expect(fn () => app(BookingService::class)->book($beto, $service, $startsAt))
        ->toThrow(SlotUnavailableException::class);

    expect(Booking::count())->toBe(1);
});

it('el candado también protege un día que todavía no tiene ninguna reserva', function () {
    // Este es el caso que se escapa si uno confía sólo en "consultar y después
    // insertar": con la tabla vacía, las dos transacciones leen cero filas y las dos
    // creen que el horario está libre.
    //
    // InnoDB en REPEATABLE READ aplica next-key locking: el SELECT ... FOR UPDATE
    // sobre el rango bloquea también los huecos, no sólo las filas que existen. Por
    // eso un INSERT concurrente dentro de ese rango queda esperando igual.
    [$service, $startsAt] = bookableSlot();

    $ana = User::factory()->create();
    $beto = User::factory()->create();

    expect(Booking::count())->toBe(0);

    $principal = DB::connection();
    $rival = rivalConnection();

    $principal->beginTransaction();

    // Ana bloquea la franja del día. No hay ni una fila que bloquear: sólo huecos.
    $principal->table('bookings')
        ->whereIn('status', BookingStatus::blocking())
        ->whereBetween('starts_at', [$startsAt->startOfDay(), $startsAt->endOfDay()])
        ->lockForUpdate()
        ->get();

    $betoQuedoBloqueado = false;

    try {
        $rival->table('bookings')->insert(
            bookingRow($service->id, $beto->id, $startsAt, $service->duration_minutes)
        );
    } catch (QueryException) {
        $betoQuedoBloqueado = true;
    }

    $principal->rollBack();

    expect($betoQuedoBloqueado)->toBeTrue(
        'Sin gap locks, dos transacciones podrían insertar el mismo turno en un día vacío.'
    );
    expect(Booking::count())->toBe(0);

    // Y sin nadie bloqueando, la reserva pasa sin problema.
    app(BookingService::class)->book($ana, $service, $startsAt);
    expect(Booking::count())->toBe(1);
});
