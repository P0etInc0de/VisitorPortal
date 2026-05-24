<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Receptionist;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Livewire\Reception\AllVisitsPage;
use App\Livewire\Reception\DashboardPage;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\PermissionHelper;
use Tests\Support\VisitHelper;
use Tests\TestCase;

class ReceptionAdministerVisitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature tests for workflows of a receptionist
     */
    public function test_receptionist_can_view_all_visits(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $visits = Visit::factory()->count(5)->create();

        $response = $this->actingAs($receptionist)
            ->get(route('reception.all-visits', ['range' => 'all']));

        // receptionist has permission to view all visits
        $response->assertStatus(200);

        // check if receptionist can see the visits
        foreach ($visits as $visit) {
            $response->assertSee((string) $visit->id);
            $response->assertSee((string) $visit->title);
        }
    }

    public function test_receptionist_can_view_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)
            ->get(route('reception.dashboard'));

        $response->assertStatus(200);
    }

    public function test_dashboard_participant_expansion_uses_bottom_toggle(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create(['first_name' => 'Edith', 'name' => 'Edwards']);
            $substituteUser = User::factory()->create();
            $visitors = new Collection([
                Visitor::factory()->create(['first_name' => 'Alice', 'name' => 'Alpha']),
                Visitor::factory()->create(['first_name' => 'Bob', 'name' => 'Beta']),
                Visitor::factory()->create(['first_name' => 'Charlie', 'name' => 'Gamma']),
                Visitor::factory()->create(['first_name' => 'Diana', 'name' => 'Delta']),
                Visitor::factory()->create(['first_name' => 'Edgar', 'name' => 'Echo']),
            ]);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Dashboard Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                $visitors
            );
            $moreParticipantsLabel = '+ 2 '.__('weitere Teilnehmende');

            Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->assertSee('sm:grid-cols-[minmax(0,1fr)_auto]', false)
                ->assertSee('w-fit justify-self-start whitespace-nowrap', false)
                ->assertSee('Host: Edith Edwards')
                ->assertSee('Alice Alpha')
                ->assertSee('Charlie Gamma')
                ->assertDontSee('Diana Delta')
                ->assertDontSee('Edgar Echo')
                ->assertSee($moreParticipantsLabel)
                ->assertDontSee(__('Weniger anzeigen'))
                ->call('toggleVisitParticipants', $visit->id)
                ->assertSee('Diana Delta')
                ->assertSee('Edgar Echo')
                ->assertDontSee($moreParticipantsLabel)
                ->assertSee(__('Weniger anzeigen'))
                ->call('toggleVisitParticipants', $visit->id)
                ->assertDontSee('Diana Delta')
                ->assertDontSee('Edgar Echo')
                ->assertSee($moreParticipantsLabel)
                ->assertDontSee(__('Weniger anzeigen'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_counts_unprepared_badges_in_configured_window(): void
    {
        $originalWindow = config('reception.badge_preparation_window_hours');

        Carbon::setTestNow(Carbon::create(2026, 5, 1, 10, 0, 0, config('app.timezone')));
        config(['reception.badge_preparation_window_hours' => 24]);

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $unpreparedVisitor = Visitor::factory()->create(['first_name' => 'Una', 'name' => 'Unprepared']);
            $preparedVisitor = Visitor::factory()->create(['first_name' => 'Paula', 'name' => 'Prepared']);
            $tooFarVisitor = Visitor::factory()->create(['first_name' => 'Fiona', 'name' => 'Future']);
            $endedVisitor = Visitor::factory()->create(['first_name' => 'Erik', 'name' => 'Ended']);
            $draftVisitor = Visitor::factory()->create(['first_name' => 'Dora', 'name' => 'Draft']);
            $upcomingVisit = Visit::factory()->create([
                'title' => 'Badge Window Visit',
                'host_user_id' => $receptionist->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(20),
                'scheduled_until' => now()->copy()->addHours(21),
            ]);
            $tooFarVisit = Visit::factory()->create([
                'host_user_id' => $receptionist->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->addHours(25),
                'scheduled_until' => now()->copy()->addHours(26),
            ]);
            $endedVisit = Visit::factory()->create([
                'host_user_id' => $receptionist->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Planned->value,
                'scheduled_from' => now()->copy()->subHours(2),
                'scheduled_until' => now()->copy()->subHour(),
            ]);
            $draftVisit = Visit::factory()->create([
                'host_user_id' => $receptionist->id,
                'substitute_user_id' => null,
                'status' => VisitStatusEnum::Draft->value,
                'scheduled_from' => now()->copy()->addHours(2),
                'scheduled_until' => now()->copy()->addHours(3),
            ]);

            $upcomingVisit->visitors()->attach($unpreparedVisitor->id);
            $upcomingVisit->visitors()->attach($preparedVisitor->id, [
                'badge_printed_at' => now(),
            ]);
            $tooFarVisit->visitors()->attach($tooFarVisitor->id);
            $endedVisit->visitors()->attach($endedVisitor->id);
            $draftVisit->visitors()->attach($draftVisitor->id);

            $component = Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->assertSee(__('Ausweise vorzubereiten'))
                ->assertSee(__('Nicht gedruckte Ausweise im Vorbereitungszeitraum'))
                ->assertDontSee(__('Offene Klärungen'));

            $this->assertMatchesRegularExpression(
                '/Ausweise vorzubereiten.*?>1<.*?Nicht gedruckte Ausweise im Vorbereitungszeitraum/s',
                $component->html()
            );
        } finally {
            config(['reception.badge_preparation_window_hours' => $originalWindow]);
            Carbon::setTestNow();
        }
    }

    /**
     * @throws \JsonException
     */
    public function test_receptionist_can_create_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $participant = Visitor::factory()->create(['created_by_user_id' => $receptionist->id]);

        $visitTitle = 'Test Visit';

        $employee = User::factory()->create();

        $substituteUser = User::factory()->create();

        // create a visit acting as the receptionist
        $response = $this->actingAs($receptionist)
            ->post(route('portal.visits.store'), [
                'host_user_id' => $employee->id,
                'substitute_user_id' => $substituteUser->id,
                'title' => $visitTitle,
                'scheduled_from' => '2026-04-29 10:00:00',
                'scheduled_until' => '2026-04-29 11:00:00',
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit Notes',
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);
        $response->assertSessionHasNoErrors();

        // check if database has attributes of the just created visit
        $this->assertDatabaseHas('visits', [
            'title' => $visitTitle,
            'notes' => 'Test Visit Notes',
        ]);

    }

    /**
     * @throws \JsonException
     */
    public function test_receptionist_cannot_edit_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $participant = Visitor::factory()->create(['created_by_user_id' => $receptionist->id]);
        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();

        // create a visit
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            'Test Visit',
            VisitStatusEnum::Planned,
            $substituteUser->id
        );

        // updated fields
        $updatedVisitTitle = 'Updated Visit Title';
        $updatedScheduledFrom = '2026-04-29 10:00:00';
        $updatedScheduledUntil = '2026-04-29 11:00:00';
        $updatedVisitNotes = 'Updated Visit Notes';

        $this->actingAs($receptionist)
            ->get(route('portal.visits.edit', $visit))
            ->assertForbidden();

        $response = $this
            ->patch(route('portal.visits.update', $visit), [
                'host_user_id' => $employee->id,
                'substitute_user_id' => $substituteUser->id,
                'title' => $updatedVisitTitle,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => $updatedVisitNotes,
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('visits', [
            'id' => $visit->id,
            'title' => $updatedVisitTitle,
        ]);
    }

    public function test_receptionist_can_cancel_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();

        $visitTitle = 'Test Visit';

        // create visit with status 'Planned'
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            $visitTitle,
            VisitStatusEnum::Planned,
            $substituteUser->id
        );

        $this
            ->actingAs($receptionist)
            ->from(route('portal.visits.show', $visit))
            ->post(route('portal.visits.cancel', $visit))
            ->assertRedirect(route('portal.visits.show', $visit));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatusEnum::Canceled,
        ]);
    }

    public function test_receptionist_visit_detail_hides_administrative_actions(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            'Reception visible visit',
            VisitStatusEnum::Planned,
            $substituteUser->id
        );

        $this->actingAs($receptionist)
            ->get(route('portal.visits.show', $visit))
            ->assertOk()
            ->assertSee('Reception visible visit')
            ->assertDontSee(__('Termin verschieben'))
            ->assertDontSee(__('Bearbeiten'))
            ->assertDontSee(__('Aktionen'))
            ->assertSee(__('Termin absagen'))
            ->assertSee(__('Besuch absagen'));
    }

    public function test_receptionist_visit_detail_hides_cancel_action_for_completed_site_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $employee = User::factory()->create(['site_id' => $receptionist->site_id]);
        $visit = Visit::factory()->create([
            'site_id' => $receptionist->site_id,
            'host_user_id' => $employee->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Completed->value,
            'scheduled_from' => now()->subHours(2),
            'scheduled_until' => now()->subHour(),
        ]);

        $this->actingAs($receptionist)
            ->get(route('portal.visits.show', $visit))
            ->assertOk()
            ->assertDontSee(__('Termin absagen'))
            ->assertDontSee(__('Besuch absagen'))
            ->assertDontSee(__('Termin verschieben'))
            ->assertDontSee(__('Bearbeiten'))
            ->assertDontSee(__('Aktionen'));
    }

    public function test_receptionist_can_check_in_visitors(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();

            $employee = User::factory()->create();

            $visitors = Visitor::factory()->count(3)->create();

            $this->assertDatabaseHas('visitors', [
                'email' => $visitors[0]->email,
            ]);

            $substituteUser = User::factory()->create();
            $visitTitle = 'Test Visit';

            // create visit with status 'Planned'
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                $visitTitle,
                VisitStatusEnum::Planned,
                $substituteUser->id,
                $visitors
            );

            Livewire::actingAs($receptionist)
                ->test(CheckInOutBoard::class)
                ->call('checkIn', $visit->id, $visitors[0]->id)
                ->assertStatus(200);

            // check if status is updated in pivot table
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitors[0]->id,
                'checked_in_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_receptionist_can_check_out_visitors(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();

            $employee = User::factory()->create();

            $visitors = Visitor::factory()->count(3)->create();

            // all visitors present in database
            foreach ($visitors as $visitor) {
                $this->assertDatabaseHas('visitors', [
                    'email' => $visitor->email,
                ]);
            }

            $substituteUser = User::factory()->create();
            $visitTitle = 'Test Visit';

            // create visit with status 'Planned'
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                $visitTitle,
                VisitStatusEnum::Planned,
                $substituteUser->id,
                $visitors
            );

            // visitors have to be checked in to be able the check them out
            $this->checkInVisitors($visit, $visitors);

            // perform the checkout
            Livewire::actingAs($receptionist)
                ->test(CheckInOutBoard::class)
                ->call('checkOut', $visit->id, $visitors[0]->id)
                ->assertStatus(200);

            // check if status is updated in pivot table
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitors[0]->id,
                'checked_out_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_operational_actions_are_blocked_for_non_planned_visits(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $service = app(VisitActionService::class);

        foreach ([VisitStatusEnum::Draft, VisitStatusEnum::Canceled, VisitStatusEnum::Completed] as $status) {
            $visitor = Visitor::factory()->create();
            $visit = Visit::factory()->create([
                'status' => $status->value,
            ]);
            $visit->visitors()->attach($visitor->id, [
                'checked_in_at' => now()->subMinutes(5),
                'checked_in_by_user_id' => $receptionist->id,
            ]);

            foreach (['checkInParticipant', 'checkOutParticipant', 'printBadge'] as $method) {
                try {
                    if ($method === 'printBadge') {
                        $service->{$method}($visit, $visitor);
                    } else {
                        $service->{$method}($visit, $visitor, $receptionist);
                    }

                    $this->fail("{$method} should be blocked for {$status->value} visits.");
                } catch (ValidationException) {
                    $this->assertDatabaseHas('visit_visitor', [
                        'visit_id' => $visit->id,
                        'visitor_id' => $visitor->id,
                        'checked_out_at' => null,
                        'badge_printed_at' => null,
                    ]);
                }
            }
        }
    }

    public function test_check_in_out_id_card_button_updates_status_without_page_reload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create(['first_name' => 'Dorothy', 'name' => 'Gale']);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Check-In/Out Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );

            Livewire::actingAs($receptionist)
                ->test(CheckInOutBoard::class)
                ->assertSee('Dorothy Gale')
                ->assertSee(__('Geplant'))
                ->assertDontSee(__('Ausweis bereit'))
                ->call('printBadge', $visit->id, $visitor->id)
                ->assertSee(__('Ausweis bereit'))
                ->assertSee(__('Ausweis'));

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'badge_printed_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_all_visits_id_card_button_updates_status_without_page_reload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create(['first_name' => 'Dorothy', 'name' => 'Gale']);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'All Visits Ausweis Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );

            $component = Livewire::actingAs($receptionist)
                ->test(AllVisitsPage::class)
                ->assertSee('Dorothy Gale')
                ->assertSee(__('Geplant'))
                ->assertDontSee(__('Ausweis bereit'))
                ->call('printBadge', $visit->id, $visitor->id)
                ->assertSee(__('Ausweis bereit'))
                ->assertSee(__('Ausweis'));

            $this->assertStringContainsString('id-card-ready-text', $component->html());
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'badge_printed_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_all_visits_hides_operational_actions_for_completed_visits(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create(['first_name' => 'Clara', 'name' => 'Completed']);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Completed All Visits',
                VisitStatusEnum::Completed,
                $substituteUser->id,
                new Collection([$visitor])
            );
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => now()->subMinutes(30),
                'checked_in_by_user_id' => $receptionist->id,
                'checked_out_at' => now()->subMinutes(5),
                'checked_out_by_user_id' => $receptionist->id,
            ]);

            $component = Livewire::actingAs($receptionist)
                ->test(AllVisitsPage::class)
                ->assertSee('Completed All Visits')
                ->assertSee('Clara Completed')
                ->assertSee(__('Ausgecheckt'))
                ->assertDontSee(__('Erneut einchecken'))
                ->assertDontSee(__('Check-out'));

            $html = $component->html();

            $this->assertStringNotContainsString('data-testid="all-visits-participant-id-card-button"', $html);
            $this->assertStringNotContainsString(route('reception.participants.badge', [$visit, $visitor]), $html);
            $this->assertStringNotContainsString('wire:click="checkIn('.$visit->id.', '.$visitor->id.')"', $html);
            $this->assertStringNotContainsString('wire:click="checkOut('.$visit->id.', '.$visitor->id.')"', $html);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_all_visits_shows_operational_actions_for_planned_visits(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitors = new Collection([
                Visitor::factory()->create(['first_name' => 'Paula', 'name' => 'Planned']),
                Visitor::factory()->create(['first_name' => 'Rhea', 'name' => 'Recheck']),
            ]);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Planned All Visits',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                $visitors
            );
            $visit->visitors()->updateExistingPivot($visitors[1]->id, [
                'checked_in_at' => now()->subMinutes(30),
                'checked_in_by_user_id' => $receptionist->id,
                'checked_out_at' => now()->subMinutes(5),
                'checked_out_by_user_id' => $receptionist->id,
            ]);

            $component = Livewire::actingAs($receptionist)
                ->test(AllVisitsPage::class)
                ->assertSee('Paula Planned')
                ->assertSee('Rhea Recheck')
                ->assertSee(__('Check-in'))
                ->assertSee(__('Erneut einchecken'));

            $this->assertSame(2, substr_count($component->html(), 'data-testid="all-visits-participant-id-card-button"'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_check_in_updates_participant_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create();
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Dashboard Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );

            Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->call('checkIn', $visit->id, $visitor->id)
                ->assertSee(__('Eingecheckt'))
                ->assertSee(__('Check-out'));

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'checked_in_at' => now()->format('Y-m-d H:i:s'),
                'checked_out_at' => null,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_check_out_updates_participant_state(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create();
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Dashboard Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $receptionist->id,
            ]);

            Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->call('checkOut', $visit->id, $visitor->id)
                ->assertSee(__('Ausgecheckt'))
                ->assertSee(__('Erneut einchecken'));

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'checked_out_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_shows_id_card_button_for_all_participant_states(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitors = new Collection([
                Visitor::factory()->create(['first_name' => 'Anna', 'name' => 'Ausweis']),
                Visitor::factory()->create(['first_name' => 'Berta', 'name' => 'Gedruckt']),
                Visitor::factory()->create(['first_name' => 'Carla', 'name' => 'Ausgecheckt']),
            ]);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Dashboard Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                $visitors
            );

            $visit->visitors()->updateExistingPivot($visitors[1]->id, [
                'badge_printed_at' => now(),
            ]);
            $visit->visitors()->updateExistingPivot($visitors[2]->id, [
                'checked_in_at' => now()->subMinutes(10),
                'checked_out_at' => now(),
            ]);

            $component = Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->assertSee(__('Ausweis'))
                ->assertSee(__('Ausweis bereit'));

            $this->assertSame(3, substr_count($component->html(), 'data-testid="dashboard-participant-id-card-button"'));
            $this->assertStringNotContainsString('>Badge<', $component->html());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_id_card_button_updates_status_without_page_reload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create(['first_name' => 'Anna', 'name' => 'Ausweis']);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Dashboard Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );

            Livewire::actingAs($receptionist)
                ->test(DashboardPage::class)
                ->assertSee(__('Geplant'))
                ->assertDontSee(__('Ausweis bereit'))
                ->call('printBadge', $visit->id, $visitor->id)
                ->assertSee(__('Ausweis bereit'))
                ->assertSee(__('Ausweis'));

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'badge_printed_at' => now()->format('Y-m-d H:i:s'),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_receptionist_is_authorized_to_check_out_from_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();
        $visitor = Visitor::factory()->create();
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            'Dashboard Visit',
            VisitStatusEnum::Planned,
            $substituteUser->id,
            new Collection([$visitor])
        );
        $visit->visitors()->updateExistingPivot($visitor->id, [
            'checked_in_at' => now(),
            'checked_in_by_user_id' => $receptionist->id,
        ]);

        Livewire::actingAs($receptionist)
            ->test(DashboardPage::class)
            ->call('checkOut', $visit->id, $visitor->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('visit_visitor', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'checked_out_by_user_id' => $receptionist->id,
        ]);
    }

    public function test_receptionist_can_create_walk_ins(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $host = (new PermissionHelper)->getUserUser();

        $walkInTitle = 'Dr';
        $walkInFirstName = 'Mira';
        $walkInLastName = 'Sample';
        $walkInEmail = 'mira.sample@example.com';
        $walkInPhoneNumber = '+493023125022';
        $walkInCompany = 'Example Industries';

        // create a walk-in
        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->set('walkInHostId', $host->id)
            ->set('walkIn.title', $walkInTitle)
            ->set('walkIn.first_name', $walkInFirstName)
            ->set('walkIn.name', $walkInLastName)
            ->set('walkIn.email', $walkInEmail)
            ->set('walkIn.phone', $walkInPhoneNumber)
            ->set('walkIn.company', $walkInCompany)
            ->call('registerWalkIn', false)
            ->assertHasNoErrors();

        // check if walk-in is present in visit table
        $this->assertDatabaseHas('visits', [
            'title' => 'Walk-in: '.$walkInTitle.' '.$walkInFirstName.' '.$walkInLastName,
            'is_walk_in' => true,
        ]);
    }

    public function test_reception_cannot_reschedule_appointments(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $scheduledFromTimeStamp = now()->subDay();
        $scheduledToTimeStamp = now()->subDay()->addMinutes(60);

        // create a visit with timestamps
        $visit = Visit::factory()->create([
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

        // check if visit created in database
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

        // create new timestamps
        $scheduledFromTimeStampUpdated = now();
        $scheduledToTimeStampUpdated = now()->addMinutes(60);

        $this->actingAs($receptionist)
            ->patch(route('portal.visits.reschedule', $visit), [
                'scheduled_from' => $scheduledFromTimeStampUpdated,
                'scheduled_until' => $scheduledToTimeStampUpdated,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

    }

    public function test_reception_reschedule_is_forbidden_before_validation(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $scheduledFromTimeStamp = now()->subDay();
        $scheduledToTimeStamp = now()->subDay()->addMinutes(60);
        $visit = Visit::factory()->create([
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

        $this->actingAs($receptionist)
            ->patch(route('portal.visits.reschedule', $visit), [
                'scheduled_from' => now(),
                'scheduled_until' => null,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);
    }

    public function test_reception_can_create_id_cards(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 1, 14, 39, 24, config('app.timezone')));

        try {
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $employee = User::factory()->create();
            $substituteUser = User::factory()->create();
            $visitor = Visitor::factory()->create(['first_name' => 'Ada', 'name' => 'Lovelace']);
            $visit = (new VisitHelper)->makeVisit(
                $employee->id,
                'Badge PDF Visit',
                VisitStatusEnum::Planned,
                $substituteUser->id,
                new Collection([$visitor])
            );
            $checkedInAt = now()->subMinutes(10);

            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => $checkedInAt,
                'checked_in_by_user_id' => $receptionist->id,
            ]);

            $pdfBuilder = \Mockery::mock(PdfBuilder::class);
            $pdfContent = "%PDF-1.4\n/MediaBox [0 0 242.88 154.08]\n%%EOF";

            Pdf::shouldReceive('view')
                ->once()
                ->with('pdf.visitor_badge', \Mockery::on(fn (array $data): bool => $data['visit']->is($visit)
                    && $data['visitor']->is($visitor)))
                ->andReturn($pdfBuilder);
            $pdfBuilder->shouldReceive('driver')->once()->with('gotenberg')->andReturnSelf();
            $pdfBuilder->shouldReceive('paperSize')->once()->with(242.88, 153.12, 'pt')->andReturnSelf();
            $pdfBuilder->shouldReceive('margins')->once()->with(0, 0, 0, 0, 'pt')->andReturnSelf();
            $pdfBuilder->shouldReceive('scale')->once()->with(1)->andReturnSelf();
            $pdfBuilder->shouldReceive('generatePdfContent')->once()->andReturn($pdfContent);

            $response = $this->actingAs($receptionist)
                ->post(route('reception.participants.badge', [$visit, $visitor]));

            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('Ada_Lovelace.pdf', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('/MediaBox [0 1 242.88 154.08]', $response->getContent());

            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'badge_printed_at' => now()->format('Y-m-d H:i:s'),
                'checked_in_at' => $checkedInAt->format('Y-m-d H:i:s'),
                'checked_out_at' => null,
            ]);
            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => VisitStatusEnum::Planned->value,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reception_can_view_own_user_permissions(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)
            ->get(route('user-permissions'));

        $response->assertStatus(200);
    }

    public function test_reception_can_view_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)
            ->get(route('reception.dashboard'));

        $response->assertStatus(200);
    }

    public function checkInVisitors(Visit $visit, Collection $visitors): void
    {
        foreach ($visitors as $visitor) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => now(),
            ]);
        }
    }
}
