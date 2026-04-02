<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    use RefreshDatabase;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name;
        $nameParts = explode(' ', $name);

        return [
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1],
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'status' => 'planned',
            'notes' => null,
        ];
    }
}
