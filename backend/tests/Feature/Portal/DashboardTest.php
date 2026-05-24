<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Portal;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_running_and_upcoming_visits_within_30_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            Visit::factory()->create([
                'title' => 'Vergangener Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(10, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(11, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Heute schon vorbei',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(9, 0),
                'scheduled_until' => now()->copy()->setTime(10, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Laufender Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(11, 0),
                'scheduled_until' => now()->copy()->setTime(12, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Besuch in 2 Tagen',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDays(2)->setTime(13, 0),
                'scheduled_until' => now()->copy()->addDays(2)->setTime(14, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Besuch in 31 Tagen',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDays(31)->setTime(13, 0),
                'scheduled_until' => now()->copy()->addDays(31)->setTime(14, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Entwurf in 3 Tagen',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Draft->value,
                'scheduled_from' => now()->copy()->addDays(3)->setTime(11, 0),
                'scheduled_until' => now()->copy()->addDays(3)->setTime(12, 0),
            ]);

            $this->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('Laufender Besuch')
                ->assertSee('Besuch in 2 Tagen')
                ->assertSee('Entwurf in 3 Tagen')
                ->assertDontSee('Vergangener Besuch')
                ->assertDontSee('Heute schon vorbei')
                ->assertDontSee('Besuch in 31 Tagen');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_limits_upcoming_visits_to_15_entries(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            foreach (range(1, 16) as $offset) {
                Visit::factory()->create([
                    'title' => sprintf('Limit Besuch %02d', $offset),
                    'host_user_id' => $user->id,
                    'substitute_user_id' => null,
                    'status' => VisitStatusEnum::Planned->value,
                    'scheduled_from' => now()->copy()->addDays($offset)->setTime(10, 0),
                    'scheduled_until' => now()->copy()->addDays($offset)->setTime(11, 0),
                ]);
            }

            $this->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('Limit Besuch 01')
                ->assertSee('Limit Besuch 15')
                ->assertDontSee('Limit Besuch 16');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_navigation_shows_authenticated_user_full_name(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $user->forceFill([
            'first_name' => 'Avery',
            'name' => 'Stone',
            'email' => 'avery.stone@example.org',
        ])->save();

        $this->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Avery Stone');
    }

    public function test_dashboard_shows_host_full_name_for_upcoming_visits(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();
            $user->forceFill([
                'first_name' => 'Ada',
                'name' => 'Lovelace',
            ])->save();

            Visit::factory()->create([
                'title' => 'Host Full Name Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDay()->setTime(15, 30),
                'scheduled_until' => now()->copy()->addDay()->setTime(16, 30),
            ]);

            $this->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('Host: Ada Lovelace')
                ->assertDontSee('Host: Lovelace');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_formats_next_visit_for_today(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            Visit::factory()->create([
                'title' => 'Laufender Termin',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(10, 30),
                'scheduled_until' => now()->copy()->setTime(12, 30),
            ]);

            Visit::factory()->create([
                'title' => 'Termin heute',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(13, 0),
                'scheduled_until' => now()->copy()->setTime(14, 0),
            ]);

            $this->withSession(['locale' => 'de'])
                ->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('heute | 13:00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_formats_next_visit_for_tomorrow(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            Visit::factory()->create([
                'title' => 'Termin morgen',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDay()->setTime(9, 30),
                'scheduled_until' => now()->copy()->addDay()->setTime(10, 30),
            ]);

            $this->withSession(['locale' => 'de'])
                ->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('morgen | 09:30');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_formats_next_visit_for_later_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            Visit::factory()->create([
                'title' => 'Termin spaeter',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDays(2)->setTime(13, 0),
                'scheduled_until' => now()->copy()->addDays(2)->setTime(14, 0),
            ]);

            $this->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('02.05. | 13:00');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_shows_started_vs_total_visits_for_today_and_week(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 11, 2, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            Visit::factory()->create([
                'title' => 'Heute gestartet',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(9, 0),
                'scheduled_until' => now()->copy()->setTime(10, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Heute spaeter',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(15, 0),
                'scheduled_until' => now()->copy()->setTime(16, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Morgen',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDay()->setTime(9, 0),
                'scheduled_until' => now()->copy()->addDay()->setTime(10, 0),
            ]);

            $this->withSession(['locale' => 'de'])
                ->actingAs($user)
                ->get(route('overview'))
                ->assertOk()
                ->assertSee('Meine Besuche heute')
                ->assertSee('1/2')
                ->assertSee('Meine Besuche diese Woche')
                ->assertSee('1/3');
        } finally {
            Carbon::setTestNow();
        }
    }
}
