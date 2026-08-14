<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();

            // 0 = domingo ... 6 = sábado (mismo criterio que Carbon::dayOfWeek).
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Un mismo tramo horario no puede cargarse dos veces para el mismo día.
            $table->unique(['day_of_week', 'start_time'], 'availabilities_day_start_unique');
            $table->index(['day_of_week', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
