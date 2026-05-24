<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use App\Enums\VisitStatusEnum;
use App\Models\MonitorSlide;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoSeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_creates_current_ada_avery_visits(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 23, 10, 15, 0, config('app.timezone')));

        try {
            $this->seed(DatabaseSeeder::class);

            $ada = User::query()
                ->where('email', 'admin@example.org')
                ->where('first_name', 'Ada')
                ->where('name', 'Avery')
                ->firstOrFail();

            $visits = Visit::query()
                ->where('host_user_id', $ada->id)
                ->whereBetween('scheduled_from', [now(), now()->copy()->addDay()])
                ->orderBy('scheduled_from')
                ->get();

            $this->assertGreaterThanOrEqual(3, $visits->count());
            $this->assertTrue($visits->every(fn (Visit $visit): bool => $visit->host_user_id === $ada->id));
            $this->assertTrue($visits->every(fn (Visit $visit): bool => $visit->status !== VisitStatusEnum::Canceled->value));
            $this->assertTrue($visits->contains(fn (Visit $visit): bool => $visit->status === VisitStatusEnum::Planned->value));
            $this->assertTrue($visits->every(fn (Visit $visit): bool => $visit->substitute_user_id === null || $visit->substitute_user_id !== $visit->host_user_id));
            $this->assertTrue($visits->every(fn (Visit $visit): bool => $visit->created_by_user_id !== null));
            $this->assertGreaterThan(0, MonitorSlide::query()->where('is_auto_generated', true)->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
