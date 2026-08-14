<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * `ends_at` se resuelve en diferido para poder leer la duración del servicio que
     * finalmente quedó asociado (venga de la factory o de un `for()` del llamador).
     *
     * Ojo: la factory NO garantiza que el turno caiga dentro de la agenda ni que esté
     * libre. Para datos de demo coherentes está BookingSeeder; esto es para tests.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'user_id' => User::factory()->client(),
            'starts_at' => $this->randomSlot(),
            'ends_at' => fn (array $attributes) => CarbonImmutable::parse($attributes['starts_at'])
                ->addMinutes($this->durationOf($attributes['service_id'])),
            'status' => BookingStatus::Confirmed,
            'notes' => fake()->boolean(25) ? fake()->sentence(6) : null,
        ];
    }

    /**
     * Reserva cancelada: libera el hueco y deja `slot_key` en NULL.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
        ]);
    }

    /**
     * Reserva ya atendida. Se la manda al pasado para que sea coherente.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
        ]);
    }

    /**
     * Reserva confirmada (estado por defecto, explícito para que se lea mejor).
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Confirmed,
        ]);
    }

    /**
     * Fija el inicio del turno; el fin se recalcula según la duración del servicio.
     */
    public function startingAt(CarbonImmutable $startsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($this->durationOf($attributes['service_id'] ?? null)),
        ]);
    }

    /**
     * Inicio alineado a la grilla de 15', dentro del horario de atención.
     */
    private function randomSlot(): CarbonImmutable
    {
        return CarbonImmutable::now()
            ->addDays(fake()->numberBetween(1, 14))
            ->setTime(fake()->numberBetween(9, 18), fake()->randomElement([0, 15, 30, 45]));
    }

    /**
     * Duración del servicio asociado. Si todavía no existe, asume 30'.
     */
    private function durationOf(mixed $serviceId): int
    {
        if ($serviceId instanceof Service) {
            return $serviceId->duration_minutes;
        }

        if (! is_numeric($serviceId)) {
            return 30;
        }

        $service = Service::query()->find($serviceId);

        return $service instanceof Service ? $service->duration_minutes : 30;
    }
}
