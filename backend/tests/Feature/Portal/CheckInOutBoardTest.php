<?php

namespace Tests\Feature\Portal;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckInOutBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_out_board_shows_only_today_and_next_48_hours(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = User::factory()->create();

            $yesterdayVisitor = Visitor::factory()->create([
                'first_name' => 'Yara',
                'name' => 'Yesterday',
            ]);
            $todayVisitor = Visitor::factory()->create([
                'first_name' => 'Tina',
                'name' => 'Today',
            ]);
            $futureVisitor = Visitor::factory()->create([
                'first_name' => 'Fiona',
                'name' => 'Future',
            ]);
            $tooFarVisitor = Visitor::factory()->create([
                'first_name' => 'Farah',
                'name' => 'TooFar',
            ]);

            $yesterdayVisit = Visit::factory()->create([
                'title' => 'Vergangener Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(10, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(11, 0),
            ]);
            $todayVisit = Visit::factory()->create([
                'title' => 'Heutiger Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(12, 0),
                'scheduled_until' => now()->copy()->setTime(13, 0),
            ]);
            $futureVisit = Visit::factory()->create([
                'title' => 'Baldiger Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(30),
                'scheduled_until' => now()->copy()->addHours(31),
            ]);
            $tooFarVisit = Visit::factory()->create([
                'title' => 'Zu ferner Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(49),
                'scheduled_until' => now()->copy()->addHours(50),
            ]);

            $yesterdayVisit->visitors()->attach($yesterdayVisitor->id);
            $todayVisit->visitors()->attach($todayVisitor->id);
            $futureVisit->visitors()->attach($futureVisitor->id);
            $tooFarVisit->visitors()->attach($tooFarVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSee('2 Treffer')
                ->assertSee('Heutiger Besuch')
                ->assertSee('Baldiger Besuch')
                ->assertDontSee('Vergangener Besuch')
                ->assertDontSee('Zu ferner Besuch')
                ->assertDontSee('Yara Yesterday')
                ->assertDontSee('Farah TooFar');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_check_in_out_board_sorts_results_by_proximity_to_now(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = User::factory()->create();

            $nearFutureVisitor = Visitor::factory()->create(['first_name' => 'Nora', 'name' => 'Nahe']);
            $recentPastVisitor = Visitor::factory()->create(['first_name' => 'Paul', 'name' => 'Vorbei']);
            $farFutureVisitor = Visitor::factory()->create(['first_name' => 'Zoe', 'name' => 'Spaeter']);

            $nearFutureVisit = Visit::factory()->create([
                'title' => 'Besuch in 1h',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHour(),
                'scheduled_until' => now()->copy()->addHours(2),
            ]);
            $recentPastVisit = Visit::factory()->create([
                'title' => 'Besuch vor 2h',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subHours(2),
                'scheduled_until' => now()->copy()->subHour(),
            ]);
            $farFutureVisit = Visit::factory()->create([
                'title' => 'Besuch morgen',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(20),
                'scheduled_until' => now()->copy()->addHours(21),
            ]);

            $nearFutureVisit->visitors()->attach($nearFutureVisitor->id);
            $recentPastVisit->visitors()->attach($recentPastVisitor->id);
            $farFutureVisit->visitors()->attach($farFutureVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSeeInOrder([
                    'Besuch in 1h',
                    'Besuch vor 2h',
                    'Besuch morgen',
                ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_check_in_out_search_does_not_fall_back_to_old_or_too_far_results(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = User::factory()->create();

            $todayVisitor = Visitor::factory()->create([
                'first_name' => 'Clara',
                'name' => 'Heute',
            ]);
            $oldVisitor = Visitor::factory()->create([
                'first_name' => 'Clara',
                'name' => 'Alt',
            ]);
            $tooFarVisitor = Visitor::factory()->create([
                'first_name' => 'Clara',
                'name' => 'Spaeter',
            ]);

            $todayVisit = Visit::factory()->create([
                'title' => 'Heutiger Clara Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(14, 0),
                'scheduled_until' => now()->copy()->setTime(15, 0),
            ]);
            $oldVisit = Visit::factory()->create([
                'title' => 'Alter Clara Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(14, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(15, 0),
            ]);
            $tooFarVisit = Visit::factory()->create([
                'title' => 'Spaeter Clara Besuch',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(60),
                'scheduled_until' => now()->copy()->addHours(61),
            ]);

            $todayVisit->visitors()->attach($todayVisitor->id);
            $oldVisit->visitors()->attach($oldVisitor->id);
            $tooFarVisit->visitors()->attach($tooFarVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->set('search', 'Clara')
                ->assertSee('1 Treffer')
                ->assertSee('Heutiger Clara Besuch')
                ->assertSee('Clara Heute')
                ->assertDontSee('Alter Clara Besuch')
                ->assertDontSee('Clara Alt')
                ->assertDontSee('Spaeter Clara Besuch')
                ->assertDontSee('Clara Spaeter');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_portal_check_in_updates_pivot_and_renders_db_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 17, 0, 0, config('app.timezone')));

        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Max',
            'name' => 'Mustermann',
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
            'status' => VisitStatusEnum::Planned->value,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
        ]);

        $visit->visitors()->attach($visitor->id);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->call('checkIn', $visit->id, $visitor->id)
            ->assertSee(__('Eingecheckt'))
            ->assertSee(__('Check-in').':')
            ->assertSee('26.04.2026 17:00');

        $pivot = DB::table('visit_visitor')
            ->where('visit_id', $visit->id)
            ->where('visitor_id', $visitor->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNull($pivot->checked_out_at);

        Carbon::setTestNow();
    }

    public function test_portal_check_out_updates_pivot_and_renders_db_state(): void
    {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Erika',
            'name' => 'Musterfrau',
        ]);
        $visit = Visit::factory()->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
            'status' => VisitStatusEnum::Planned->value,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
        ]);

        $visit->visitors()->attach($visitor->id, [
            'checked_in_at' => now()->subMinutes(5),
            'checked_in_by_user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->call('checkOut', $visit->id, $visitor->id)
            ->assertSee(__('Ausgecheckt'))
            ->assertSee(__('Check-out').':');

        $pivot = DB::table('visit_visitor')
            ->where('visit_id', $visit->id)
            ->where('visitor_id', $visitor->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNotNull($pivot->checked_out_at);
    }

    public function test_walk_in_host_selection_renders_search_dropdown(): void
    {
        $user = User::factory()->create([
            'first_name' => 'User',
            'name' => 'Empfang',
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertSee(__('Ansprechpartner'))
            ->assertSee(__('Namen suchen'))
            ->assertSee(__('Keine Treffer'))
            ->assertDontSeeHtml('<select class="select select-bordered w-full');
    }

    public function test_walk_in_host_selection_excludes_welcome_monitor_users(): void
    {
        $user = User::factory()->create([
            'first_name' => 'User',
            'name' => 'Empfang',
        ]);
        $welcomeMonitor = User::factory()->create([
            'first_name' => 'User',
            'name' => 'WelcomeMonitor',
        ]);
        Role::firstOrCreate(['name' => 'welcome_monitor', 'guard_name' => 'web']);
        $welcomeMonitor->assignRole('welcome_monitor');

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertDontSee('User WelcomeMonitor');
    }

    public function test_walk_in_reuses_existing_visitor_without_overwriting_and_marks_visit_as_walk_in(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 10, 12, 0, 0, config('app.timezone')));

        try {
            $user = User::factory()->create();
            $host = User::factory()->create();
            $existingVisitor = Visitor::factory()->create([
                'first_name' => 'Existing',
                'name' => 'Person',
                'email' => 'duplicate@example.com',
                'phone' => '123456789',
                'company' => 'Existing Company',
            ]);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->set('walkInHostId', (string) $host->id)
                ->set('walkIn.first_name', 'Walk')
                ->set('walkIn.name', 'In')
                ->set('walkIn.email', 'duplicate@example.com')
                ->set('walkIn.phone', '123456789')
                ->set('walkIn.company', 'Walk Company')
                ->call('registerWalkIn')
                ->assertHasNoErrors();

            $existingVisitor->refresh();

            $this->assertSame('Existing', $existingVisitor->first_name);
            $this->assertSame('Person', $existingVisitor->name);
            $this->assertSame('Existing Company', $existingVisitor->company);

            $walkInVisit = Visit::query()
                ->where('is_walk_in', true)
                ->where('title', 'Walk-in: Existing Person')
                ->first();

            $this->assertNotNull($walkInVisit);
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $walkInVisit->id,
                'visitor_id' => $existingVisitor->id,
                'checked_in_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_walk_in_rejects_conflicting_existing_email_and_phone_matches(): void
    {
        $user = User::factory()->create();
        $host = User::factory()->create();
        Visitor::factory()->create([
            'email' => 'email-owner@example.com',
            'phone' => '111111',
        ]);
        Visitor::factory()->create([
            'email' => 'phone-owner@example.com',
            'phone' => '222222',
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->set('walkInHostId', (string) $host->id)
            ->set('walkIn.first_name', 'Conflict')
            ->set('walkIn.name', 'Person')
            ->set('walkIn.email', 'email-owner@example.com')
            ->set('walkIn.phone', '222222')
            ->call('registerWalkIn')
            ->assertHasErrors(['walkIn.email']);

        $this->assertDatabaseMissing('visits', [
            'title' => 'Walk-in: Conflict Person',
        ]);
    }
}
