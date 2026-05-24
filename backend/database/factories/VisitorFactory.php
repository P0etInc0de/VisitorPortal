<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Factories;

use App\Enums\SalutationEnum;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    protected $model = Visitor::class;

    protected static int $demoPhoneIndex = 0;

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
            'email' => fake()->safeEmail(),
            'phone' => $this->demoPhone(),
            'company' => 'Example '.$this->faker->randomElement(['Industries', 'Consulting', 'Engineering', 'Systems', 'Logistics', 'Labs']),
            'notes' => null,
            'salutation' => SalutationEnum::Mr,
            'title' => 'Dr.',
        ];
    }

    private function demoPhone(): string
    {
        return sprintf('+493023125%03d', (self::$demoPhoneIndex++) % 100);
    }
}
