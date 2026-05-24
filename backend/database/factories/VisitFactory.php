<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Factories;

use App\Enums\VisitStatusEnum;
use App\Models\Site;
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
        $site = Site::default();
        $host = User::factory()->create(['site_id' => $site->id]);
        $createdBy = User::factory()->create(['site_id' => $site->id]);

        return [
            'site_id' => $site->id,
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'created_by_user_id' => $createdBy->id,
            'scheduled_from' => Carbon::now(),
            'scheduled_until' => Carbon::now()->addHour(1),
            'status' => VisitStatusEnum::Planned->value,
            'is_confidential' => false,
            'is_walk_in' => false,
            'notes' => 'test',
            'title' => 'test',
        ];
    }
}
