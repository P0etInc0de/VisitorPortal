<?php

namespace Database\Factories;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'responsible_user_id' => $user->id,
            'user_id' => $user->id,
            'scheduled_from' => Carbon::now(),
            'scheduled_until' => Carbon::now()->addHour(1),
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'test',
        ];
    }
}
