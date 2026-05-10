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
        $host = User::factory()->create();
        $responsibleUser = User::factory()->create();

        return [
            'responsible_user_id' => $responsibleUser->id,
            'user_id' => $host->id,
            'scheduled_from' => Carbon::now(),
            'scheduled_until' => Carbon::now()->addHour(1),
            'status' => VisitStatusEnum::Planned->value,
            'is_walk_in' => false,
            'notes' => 'test',
            'title' => 'test',
        ];
    }
}
