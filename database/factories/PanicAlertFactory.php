<?php

namespace Database\Factories;

use App\Models\Apartment;
use App\Models\PanicAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PanicAlert>
 */
class PanicAlertFactory extends Factory
{
    protected $model = PanicAlert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'apartment_id' => Apartment::factory(),
            'lat' => $this->faker->latitude(-4.0, 12.0), // Colombia coordinates range
            'lng' => $this->faker->longitude(-79.0, -66.0), // Colombia coordinates range
            'status' => $this->faker->randomElement(['triggered', 'confirmed', 'resolved', 'cancelled']),
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the panic alert is triggered.
     */
    public function triggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'triggered',
            'created_at' => now(),
        ]);
    }

    /**
     * Indicate that the panic alert is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the panic alert is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }

    /**
     * Indicate that the panic alert is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the panic alert has no location.
     */
    public function withoutLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'lat' => null,
            'lng' => null,
        ]);
    }
}