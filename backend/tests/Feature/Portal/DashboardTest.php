<?php

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
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(10, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(11, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Heute schon vorbei',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(9, 0),
                'scheduled_until' => now()->copy()->setTime(10, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Laufender Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(11, 0),
                'scheduled_until' => now()->copy()->setTime(12, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Besuch in 2 Tagen',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDays(2)->setTime(13, 0),
                'scheduled_until' => now()->copy()->addDays(2)->setTime(14, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Besuch in 31 Tagen',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addDays(31)->setTime(13, 0),
                'scheduled_until' => now()->copy()->addDays(31)->setTime(14, 0),
            ]);

            Visit::factory()->create([
                'title' => 'Entwurf in 3 Tagen',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
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
                    'user_id' => $user->id,
                    'responsible_user_id' => $user->id,
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
}
