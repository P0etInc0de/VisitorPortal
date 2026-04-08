<?php

namespace Database\Factories;

use App\Enums\VisitorStatusEnum;
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
        return [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'status' => VisitorStatusEnum::Planned,
            'notes' => null,
        ];
    }
}
