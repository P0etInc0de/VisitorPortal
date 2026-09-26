<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Factories;

use App\Models\Kiosk;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kiosk>
 */
class KioskFactory extends Factory
{
    protected $model = Kiosk::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $site = Site::default();
        $host = User::factory()->create(['site_id' => $site->id]);

        return [
            'site_id' => $site->id,
            'walk_in_host_user_id' => $host->id,
            'created_by_user_id' => null,
            'name' => 'Empfangs-Kiosk',
            'token' => Kiosk::generateToken(),
            'is_active' => true,
            'allow_walk_in' => true,
            'allow_check_out' => true,
            'require_signature' => false,
        ];
    }
}
