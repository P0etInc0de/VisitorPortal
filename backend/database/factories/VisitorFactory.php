<?php

namespace Database\Factories;

use App\Enums\GenderEnum;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'name' => $this->faker->lastName(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'notes' => null,
            'gender' => GenderEnum::Male,
            'title' => 'Dr.',
        ];
    }
}
