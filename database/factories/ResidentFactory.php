<?php

namespace Database\Factories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResidentFactory extends Factory
{
    protected $model = Resident::class;

    public function definition(): array
    {
        return [];
        // return [
        //     'document_type' => $this->faker->randomElement(['CC', 'CE', 'TI', 'PP']),
        //     'document_number' => $this->faker->unique()->numerify('##########'),
        //     'first_name' => $this->faker->firstName(),
        //     'last_name' => $this->faker->lastName(),
        //     'email' => $this->faker->unique()->safeEmail(),
        //     'phone' => $this->faker->optional()->numerify('###-####'),
        //     'mobile_phone' => $this->faker->optional()->numerify('###-###-####'),
        //     'birth_date' => $this->faker->optional()->dateTimeBetween('-80 years', '-18 years'),
        //     'gender' => $this->faker->optional()->randomElement(['M', 'F', 'Other']),
        //     'emergency_contact' => $this->faker->optional()->text(200),
        //     'apartment_number' => $this->faker->numerify('##'),
        //     'tower' => $this->faker->optional()->randomElement(['A', 'B', 'C', 'D']),
        //     'resident_type' => $this->faker->randomElement(['Owner', 'Tenant', 'Family']),
        //     'status' => $this->faker->randomElement(['Active', 'Inactive']),
        //     'start_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
        //     'end_date' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
        //     'notes' => $this->faker->optional()->text(500),
        //     'documents' => $this->faker->optional()->randomElements([
        //         'id_copy.pdf',
        //         'contract.pdf',
        //         'authorization.pdf',
        //         'proof_of_income.pdf',
        //     ], $this->faker->numberBetween(0, 3)),
        // ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Inactive',
        ]);
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'resident_type' => 'Owner',
        ]);
    }

    public function tenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'resident_type' => 'Tenant',
        ]);
    }

    public function family(): static
    {
        return $this->state(fn (array $attributes) => [
            'resident_type' => 'Family',
        ]);
    }

    public function withTower(string $tower): static
    {
        return $this->state(fn (array $attributes) => [
            'tower' => $tower,
        ]);
    }

    public function inApartment(string $apartment, ?string $tower = null): static
    {
        return $this->state(fn (array $attributes) => [
            'apartment_number' => $apartment,
            'tower' => $tower,
        ]);
    }
}
