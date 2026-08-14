<?php

namespace Database\Factories;

use App\Models\Availability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Por defecto genera un tramo de mañana en un día hábil (martes a viernes).
     * El unique de (day_of_week, start_time) obliga a que quien cree varias filas
     * pase el día y la hora a mano o use los estados de abajo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->numberBetween(2, 5),
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'is_active' => true,
        ];
    }

    /**
     * Tramo para un día concreto de la semana (0 = domingo ... 6 = sábado).
     */
    public function onDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    /**
     * Tramo con horario explícito, en formato H:i:s.
     */
    public function between(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Tramo desactivado: la agenda lo ignora al calcular huecos.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
