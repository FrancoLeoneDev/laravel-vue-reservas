<?php

use App\Enums\BookingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default(BookingStatus::Confirmed->value);
            $table->text('notes')->nullable();

            // --- Última línea de defensa contra la doble reserva -------------------
            //
            // `slot_key` es una columna generada (STORED) que vale `starts_at` mientras
            // la reserva ocupa la agenda, y NULL cuando fue cancelada. Sobre ella va un
            // índice UNIQUE.
            //
            // El truco está en que MySQL no considera los NULL a la hora de evaluar la
            // unicidad: varias reservas canceladas para las 10:00 conviven sin problema,
            // pero sólo puede existir UNA activa que arranque a las 10:00.
            //
            // Así, aunque la validación de PHP fallara por completo (bug, race condition,
            // un `php artisan tinker` con la mano pesada), la base de datos rechaza el
            // INSERT con un error de clave duplicada. La app lo traduce a un mensaje
            // legible en BookingService::book().
            $table->dateTime('slot_key')
                ->storedAs("CASE WHEN status = '".BookingStatus::Cancelled->value."' THEN NULL ELSE starts_at END")
                ->nullable();

            $table->timestamps();

            $table->unique('slot_key', 'bookings_active_slot_unique');

            // Índice sobre el rango temporal: además de acelerar la agenda, es el que
            // usa el SELECT ... FOR UPDATE de BookingService para tomar next-key locks
            // sobre la franja del día y serializar a dos usuarios concurrentes.
            $table->index('starts_at');
            $table->index(['status', 'starts_at']);
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
