<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Duraciones válidas: siempre múltiplos del paso de la grilla (15').
     *
     * @var array<int, int>
     */
    protected const DURACIONES = [15, 30, 45, 60, 90, 120];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(12),
            'duration_minutes' => fake()->randomElement(self::DURACIONES),
            'price' => fake()->numberBetween(60, 600) * 250,
            'is_active' => true,
        ];
    }

    /**
     * Servicio dado de baja: no se puede reservar.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Servicio con una duración concreta (debe ser múltiplo de 15').
     */
    public function duration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $minutes,
        ]);
    }
}
