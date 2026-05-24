<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class CheckInOutBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_out_board_shows_only_visits_check_in_window_and_next_48_hours(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            $yesterdayVisitor = Visitor::factory()->create([
                'first_name' => 'Yara',
                'name' => 'Yesterday',
            ]);
            $todayVisitor = Visitor::factory()->create([
                'first_name' => 'Tina',
                'name' => 'Today',
            ]);
            $endedTodayVisitor = Visitor::factory()->create([
                'first_name' => 'Edgar',
                'name' => 'Ended',
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
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(10, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(11, 0),
            ]);
            $todayVisit = Visit::factory()->create([
                'title' => 'Heutiger Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(12, 0),
                'scheduled_until' => now()->copy()->setTime(13, 0),
            ]);
            $endedTodayVisit = Visit::factory()->create([
                'title' => 'Beendeter Besuch heute',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(8, 0),
                'scheduled_until' => now()->copy()->setTime(9, 0),
            ]);
            $futureVisit = Visit::factory()->create([
                'title' => 'Baldiger Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(30),
                'scheduled_until' => now()->copy()->addHours(31),
            ]);
            $tooFarVisit = Visit::factory()->create([
                'title' => 'Zu ferner Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(49),
                'scheduled_until' => now()->copy()->addHours(50),
            ]);

            $yesterdayVisit->visitors()->attach($yesterdayVisitor->id);
            $todayVisit->visitors()->attach($todayVisitor->id);
            $endedTodayVisit->visitors()->attach($endedTodayVisitor->id);
            $futureVisit->visitors()->attach($futureVisitor->id);
            $tooFarVisit->visitors()->attach($tooFarVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSee('2 Treffer')
                ->assertSee('Heutiger Besuch')
                ->assertSee('Baldiger Besuch')
                ->assertDontSee('Vergangener Besuch')
                ->assertDontSee('Beendeter Besuch heute')
                ->assertDontSee('Zu ferner Besuch')
                ->assertDontSee('Yara Yesterday')
                ->assertDontSee('Edgar Ended')
                ->assertDontSee('Farah TooFar');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_check_in_out_board_uses_configured_check_in_window(): void
    {
        $originalWindow = config('reception.check_in_window_hours');

        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));
        config(['reception.check_in_window_hours' => 12]);

        try {
            $user = (new PermissionHelper)->getReceptionistUser();
            $visibleVisitor = Visitor::factory()->create(['first_name' => 'Vera', 'name' => 'Visible']);
            $outsideVisitor = Visitor::factory()->create(['first_name' => 'Oscar', 'name' => 'Outside']);
            $visibleVisit = Visit::factory()->create([
                'title' => 'Configured Visible Visit',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(11),
                'scheduled_until' => now()->copy()->addHours(12),
            ]);
            $outsideVisit = Visit::factory()->create([
                'title' => 'Configured Outside Visit',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(13),
                'scheduled_until' => now()->copy()->addHours(14),
            ]);

            $visibleVisit->visitors()->attach($visibleVisitor->id);
            $outsideVisit->visitors()->attach($outsideVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSee('Eincheckbare Termine bis zu 12 Stunden im Voraus')
                ->assertSee('1 Treffer')
                ->assertSee('Configured Visible Visit')
                ->assertDontSee('Configured Outside Visit')
                ->call('checkIn', $outsideVisit->id, $outsideVisitor->id);

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $outsideVisit->id,
                'visitor_id' => $outsideVisitor->id,
                'checked_in_at' => null,
            ]);
        } finally {
            config(['reception.check_in_window_hours' => $originalWindow]);
            Carbon::setTestNow();
        }
    }

    public function test_check_in_out_board_sorts_planned_by_start_and_checked_in_by_end(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

            $plannedSoonVisitor = Visitor::factory()->create(['first_name' => 'Nora', 'name' => 'Nahe']);
            $checkedInVisitor = Visitor::factory()->create(['first_name' => 'Paul', 'name' => 'Laufend']);
            $plannedLaterVisitor = Visitor::factory()->create(['first_name' => 'Zoe', 'name' => 'Spaeter']);

            $plannedSoonVisit = Visit::factory()->create([
                'title' => 'Geplanter Besuch 10:15',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(10, 15),
                'scheduled_until' => now()->copy()->setTime(11, 0),
            ]);
            $checkedInVisit = Visit::factory()->create([
                'title' => 'Eingecheckter Besuch bis 10:30',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(8, 0),
                'scheduled_until' => now()->copy()->setTime(10, 30),
            ]);
            $plannedLaterVisit = Visit::factory()->create([
                'title' => 'Geplanter Besuch 11:00',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(11, 0),
                'scheduled_until' => now()->copy()->setTime(12, 0),
            ]);

            $plannedSoonVisit->visitors()->attach($plannedSoonVisitor->id);
            $checkedInVisit->visitors()->attach($checkedInVisitor->id, [
                'checked_in_at' => now()->copy()->setTime(8, 30),
                'checked_in_by_user_id' => $user->id,
            ]);
            $plannedLaterVisit->visitors()->attach($plannedLaterVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSeeInOrder([
                    'Geplanter Besuch 10:15',
                    'Eingecheckter Besuch bis 10:30',
                    'Geplanter Besuch 11:00',
                ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_checked_out_participants_are_sorted_after_regular_actions(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();
            $checkedOutVisitor = Visitor::factory()->create(['first_name' => 'Alice', 'name' => 'Recheck']);
            $plannedVisitor = Visitor::factory()->create(['first_name' => 'Zoe', 'name' => 'Planned']);
            $visit = Visit::factory()->create([
                'title' => 'Shared Training',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(10, 15),
                'scheduled_until' => now()->copy()->setTime(11, 0),
            ]);

            $visit->visitors()->attach($checkedOutVisitor->id, [
                'checked_in_at' => now()->copy()->setTime(9, 0),
                'checked_out_at' => now()->copy()->setTime(9, 30),
                'checked_in_by_user_id' => $user->id,
                'checked_out_by_user_id' => $user->id,
            ]);
            $visit->visitors()->attach($plannedVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSeeInOrder([
                    'Zoe Planned',
                    'Alice Recheck',
                    __('Erneut einchecken'),
                ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_ended_visit_only_keeps_active_checked_in_participants_visible_for_checkout(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();
            $activeVisitor = Visitor::factory()->create(['first_name' => 'Ava', 'name' => 'Active']);
            $noShowVisitor = Visitor::factory()->create(['first_name' => 'Nina', 'name' => 'NoShow']);
            $visit = Visit::factory()->create([
                'title' => 'Ended With Active Checkout',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subHours(3),
                'scheduled_until' => now()->copy()->subHour(),
            ]);

            $visit->visitors()->attach($activeVisitor->id, [
                'checked_in_at' => now()->copy()->subHours(2),
                'checked_in_by_user_id' => $user->id,
            ]);
            $visit->visitors()->attach($noShowVisitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->assertSee('Ava Active')
                ->assertSee(__('Check-out'))
                ->assertDontSee('Nina NoShow');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_check_in_is_ignored_after_visit_window_ended(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();
            $visitor = Visitor::factory()->create();
            $visit = Visit::factory()->create([
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subHours(3),
                'scheduled_until' => now()->copy()->subHour(),
            ]);
            $visit->visitors()->attach($visitor->id);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->call('checkIn', $visit->id, $visitor->id);

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'checked_in_at' => null,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_check_in_out_search_does_not_fall_back_to_old_or_too_far_results(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 30, 10, 0, 0, config('app.timezone')));

        try {
            $user = (new PermissionHelper)->getReceptionistUser();

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
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->setTime(14, 0),
                'scheduled_until' => now()->copy()->setTime(15, 0),
            ]);
            $oldVisit = Visit::factory()->create([
                'title' => 'Alter Clara Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subDay()->setTime(14, 0),
                'scheduled_until' => now()->copy()->subDay()->setTime(15, 0),
            ]);
            $tooFarVisit = Visit::factory()->create([
                'title' => 'Spaeter Clara Besuch',
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
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

    public function test_check_in_out_board_hides_contact_details_without_permission(): void
    {
        $user = (new PermissionHelper)->getIndividualUser([
            'ViewSite:Visit',
            'ViewSite:Visitor',
        ], 'site viewer');

        $visitor = Visitor::factory()->create([
            'first_name' => 'Contact',
            'name' => 'Hidden',
            'email' => 'contact.hidden@example.test',
            'phone' => '+49 111 222333',
        ]);
        $visit = Visit::factory()->create([
            'title' => 'Contact Detail Visit',
            'site_id' => $user->site_id,
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Planned->value,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
        ]);
        $visit->visitors()->attach($visitor->id);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertSee('Contact Hidden')
            ->assertDontSee('contact.hidden@example.test')
            ->assertDontSee('+49 111 222333')
            ->set('search', 'contact.hidden@example.test')
            ->assertDontSee('Contact Hidden')
            ->assertDontSee('Contact Detail Visit');
    }

    public function test_portal_check_in_updates_pivot_and_renders_db_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 26, 17, 0, 0, config('app.timezone')));

        $user = (new PermissionHelper)->getReceptionistUser();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'Sample',
        ]);
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
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
            ->assertSee('26.04.2026 17:00')
            ->assertDontSee(__('Teilnehmender wurde eingecheckt.'));

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
        $user = (new PermissionHelper)->getReceptionistUser();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Erika',
            'name' => 'Musterfrau',
        ]);
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
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
            ->assertSee(__('Check-out').':')
            ->assertDontSee(__('Teilnehmender wurde ausgecheckt.'));

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
        $user = (new PermissionHelper)->getReceptionistUser();
        $user->update([
            'first_name' => 'User',
            'name' => 'Empfang',
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertSee(__('Ansprechpartner'))
            ->assertSee('lg:max-h-[calc(100dvh-13.5rem)]', false)
            ->assertSee('lg:-mr-5 lg:pr-5', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee(__('Namen suchen'))
            ->assertSee(__('Keine Treffer'))
            ->assertDontSeeHtml('<select class="select select-bordered w-full');
    }

    public function test_walk_in_host_selection_excludes_welcome_monitor_users(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $user->update([
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
            $user = (new PermissionHelper)->getReceptionistUser();
            $host = User::factory()->create();
            $existingVisitor = Visitor::factory()->create([
                'first_name' => 'Existing',
                'name' => 'Person',
                'email' => 'duplicate@example.com',
                'phone' => '+493023125023',
                'company' => 'Existing Company',
                'created_by_user_id' => $user->id,
            ]);

            Livewire::actingAs($user)
                ->test(CheckInOutBoard::class)
                ->set('walkInHostId', (string) $host->id)
                ->set('walkIn.first_name', 'Walk')
                ->set('walkIn.name', 'In')
                ->set('walkIn.email', 'duplicate@example.com')
                ->set('walkIn.phone', '+493023125023')
                ->set('walkIn.company', 'Walk Company')
                ->call('registerWalkIn')
                ->assertHasNoErrors();

            $existingVisitor->refresh();

            $this->assertSame('Existing', $existingVisitor->first_name);
            $this->assertSame('Person', $existingVisitor->name);
            $this->assertSame('Existing Company', $existingVisitor->company);

            $walkInVisit = Visit::query()
                ->where('is_walk_in', true)
                ->whereHas('visitors', fn ($query) => $query->where('visitors.id', $existingVisitor->id))
                ->first();

            $this->assertNotNull($walkInVisit);
            $this->assertStringContainsString('Existing Person', $walkInVisit->title);
            $this->assertTrue($walkInVisit->is_confidential);
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $walkInVisit->id,
                'visitor_id' => $existingVisitor->id,
                'checked_in_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_walk_in_confidential_toggle_can_be_disabled(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->set('walkInHostId', (string) $host->id)
            ->set('walkIn.first_name', 'Public')
            ->set('walkIn.name', 'Walkin')
            ->set('walkInIsConfidential', false)
            ->call('registerWalkIn')
            ->assertHasNoErrors();

        $this->assertFalse(Visit::query()->where('is_walk_in', true)->sole()->is_confidential);
    }

    public function test_walk_in_does_not_reuse_existing_visitor_by_phone(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create();
        $existingVisitor = Visitor::factory()->create([
            'first_name' => 'Phone',
            'name' => 'Owner',
            'phone' => '222222',
            'created_by_user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->set('walkInHostId', (string) $host->id)
            ->set('walkIn.first_name', 'Shared')
            ->set('walkIn.name', 'Person')
            ->set('walkIn.phone', '222222')
            ->call('registerWalkIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visitors', [
            'id' => $existingVisitor->id,
            'first_name' => 'Phone',
            'name' => 'Owner',
            'phone' => '222222',
        ]);
        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Shared',
            'name' => 'Person',
            'phone' => '222222',
        ]);
        $this->assertDatabaseHas('visits', [
            'title' => 'Walk-in: Shared Person',
        ]);
        $this->assertSame(2, Visitor::query()->where('phone', '222222')->count());
    }
}
