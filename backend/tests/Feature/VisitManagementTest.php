<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use App\Livewire\Portal\VisitShowPage;
use App\Models\RecurringVisitSeries;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_create_page_defaults_status_to_planned(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('option value="planned" selected', false)
            ->assertSee(__('Diese werden nicht automatisch auf Willkommensmonitoren angezeigt.'));
    }

    public function test_visit_create_page_scopes_host_and_substitute_options(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->update([
            'first_name' => 'User',
            'name' => 'Admin',
        ]);

        $substitute = (new PermissionHelper)->getUser();
        $substitute->update([
            'first_name' => 'Clara',
            'name' => 'Bergmann',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('User Admin');

        $this->assertMatchesRegularExpression(
            '/<input[^>]+name="host_user_id"[^>]+value="'.$user->id.'"/i',
            $response->getContent()
        );
        $response->assertDontSee('Clara Bergmann');
    }

    public function test_visit_create_page_does_not_show_welcome_monitor_user_in_host_dropdowns(): void
    {
        $user = (new PermissionHelper)->getUser();
        $welcomeMonitor = (new PermissionHelper)->getWelcomeMonitorUser();
        $welcomeMonitor->update([
            'first_name' => 'User',
            'name' => 'WelcomeMonitor',
        ]);

        $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertDontSee('User WelcomeMonitor');
    }

    public function test_visit_requires_different_host_and_substitute(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'host_user_id' => $user->id,
                'substitute_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '+493023125024',
                        'company' => 'Example Industries',
                    ],
                ],
            ])
            ->assertSessionHasErrors('substitute_user_id');
    }

    public function test_visit_can_be_created_with_multiple_participants(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'host_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'notes' => 'Empfang briefen',
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '+493023125024',
                        'company' => 'Example Industries',
                    ],
                    [
                        'first_name' => 'Evan',
                        'name' => 'Sample',
                        'email' => 'max@example.com',
                        'phone' => '+493023125025',
                        'company' => 'Sample Logistics',
                    ],
                ],
            ]);

        $visit = Visit::query()->firstOrFail();

        $response->assertRedirect(route('portal.visits.show', $visit));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => 'Technik-Rundgang',
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
            'created_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('visitors', 2);
        $this->assertDatabaseCount('visit_visitor', 2);
    }

    public function test_visit_creation_allows_distinct_visitors_with_same_phone(): void
    {
        $user = (new PermissionHelper)->getUser();
        Visitor::factory()->create([
            'first_name' => 'Existing',
            'name' => 'SharedPhone',
            'phone' => '+493023125099',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Shared phone visit',
                'host_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'participants' => [
                    [
                        'first_name' => 'New',
                        'name' => 'SharedPhone',
                        'phone' => '+493023125099',
                    ],
                ],
            ]);

        $visit = Visit::query()->where('title', 'Shared phone visit')->firstOrFail();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.visits.show', $visit));

        $this->assertSame(2, Visitor::query()->where('phone', '+493023125099')->count());
        $this->assertDatabaseHas('visitors', [
            'first_name' => 'New',
            'name' => 'SharedPhone',
            'phone' => '+493023125099',
        ]);
    }

    public function test_recurring_visit_can_be_created_with_total_occurrence_count(): void
    {
        $user = (new PermissionHelper)->getUser();
        $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Wöchentliche Sicherheitsrunde',
                'host_user_id' => $user->id,
                'scheduled_from' => '2026-05-01 09:00:00',
                'scheduled_until' => '2026-05-01 10:00:00',
                'status' => 'planned',
                'notes' => 'Serie testen',
                'recurrence_enabled' => '1',
                'recurrence_frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
                'recurrence_end_type' => RecurringVisitSeries::END_COUNT,
                'recurrence_occurrence_count' => 3,
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);

        $firstVisit = Visit::query()->orderBy('scheduled_from')->firstOrFail();

        $response->assertRedirect(route('portal.visits.show', $firstVisit));

        $this->assertDatabaseCount('recurring_visit_series', 1);
        $this->assertDatabaseCount('visits', 3);
        $this->assertDatabaseCount('visit_visitor', 3);

        $visits = Visit::query()->orderBy('scheduled_from')->get();

        $this->assertSame(['2026-05-01', '2026-05-08', '2026-05-15'], $visits
            ->map(fn (Visit $visit) => $visit->scheduled_from->format('Y-m-d'))
            ->all());
        $this->assertSame([1, 2, 3], $visits->pluck('recurrence_occurrence_number')->all());
        $this->assertTrue($visits->every(fn (Visit $visit) => $visit->recurring_visit_series_id === $firstVisit->recurring_visit_series_id));
    }

    public function test_future_series_update_skips_individually_modified_occurrences(): void
    {
        $user = (new PermissionHelper)->getUser();
        $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

        $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Regeltermin',
                'host_user_id' => $user->id,
                'scheduled_from' => '2026-05-01 09:00:00',
                'scheduled_until' => '2026-05-01 10:00:00',
                'status' => 'planned',
                'recurrence_enabled' => '1',
                'recurrence_frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
                'recurrence_end_type' => RecurringVisitSeries::END_COUNT,
                'recurrence_occurrence_count' => 4,
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);

        $modifiedVisit = Visit::query()->where('recurrence_occurrence_number', 3)->firstOrFail();
        $modifiedVisit->update([
            'title' => 'Individuelle Änderung',
            'recurrence_is_modified' => true,
        ]);

        $selectedVisit = Visit::query()->where('recurrence_occurrence_number', 2)->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->patch(route('portal.visits.update', $selectedVisit), [
                'title' => 'Neue Zukunftsserie',
                'host_user_id' => $user->id,
                'scheduled_from' => '2026-05-08 12:00:00',
                'scheduled_until' => '2026-05-08 13:00:00',
                'status' => 'planned',
                'recurrence_enabled' => '1',
                'recurrence_update_scope' => RecurringVisitSeries::UPDATE_FUTURE,
                'recurrence_frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
                'recurrence_end_type' => RecurringVisitSeries::END_COUNT,
                'recurrence_occurrence_count' => 4,
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);

        $response->assertRedirect(route('portal.visits.show', $selectedVisit));

        $this->assertSame('Regeltermin', Visit::query()->where('recurrence_occurrence_number', 1)->firstOrFail()->title);
        $this->assertSame('Neue Zukunftsserie', Visit::query()->where('recurrence_occurrence_number', 2)->firstOrFail()->title);
        $this->assertSame('Individuelle Änderung', Visit::query()->where('recurrence_occurrence_number', 3)->firstOrFail()->title);
        $this->assertSame('Neue Zukunftsserie', Visit::query()->where('recurrence_occurrence_number', 4)->firstOrFail()->title);
    }

    public function test_forever_series_generates_until_thirty_month_horizon(): void
    {
        Carbon::setTestNow('2026-05-01 08:00:00');

        try {
            $user = (new PermissionHelper)->getUser();
            $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

            $this
                ->actingAs($user)
                ->post(route('portal.visits.store'), [
                    'title' => 'Endlose Wartung',
                    'host_user_id' => $user->id,
                    'scheduled_from' => '2026-05-01 09:00:00',
                    'scheduled_until' => '2026-05-01 10:00:00',
                    'status' => 'planned',
                    'recurrence_enabled' => '1',
                    'recurrence_frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
                    'recurrence_end_type' => RecurringVisitSeries::END_FOREVER,
                    'participants' => [
                        ['visitor_id' => $participant->id],
                    ],
                ])
                ->assertSessionHasNoErrors();

            $series = RecurringVisitSeries::query()->sole();

            $this->assertSame('2028-11-01', $series->generated_until->format('Y-m-d'));
            $this->assertGreaterThan(100, Visit::query()->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_create_visit_page_cloaks_hidden_participant_sections(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('x-show="addingParticipant" x-cloak style="display: none;"', false)
            ->assertSee('x-show="addingNewParticipant" x-cloak style="display: none;"', false);
    }

    public function test_my_visits_page_shows_only_own_and_substitute_visits(): void
    {
        $user = (new PermissionHelper)->getUser();
        $otherUser = (new PermissionHelper)->getUser();

        Visit::factory()->create([
            'title' => 'Eigener Host-Besuch',
            'host_user_id' => $user->id,
            'substitute_user_id' => $otherUser->id,
        ]);

        Visit::factory()->create([
            'title' => 'Eigener Vertretungs-Besuch',
            'host_user_id' => $otherUser->id,
            'substitute_user_id' => $user->id,
        ]);

        Visit::factory()->create([
            'title' => 'Fremder Besuch',
            'host_user_id' => $otherUser->id,
            'substitute_user_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(route('portal.my-visits'))
            ->assertOk()
            ->assertSee(__('Meine Besuche'))
            ->assertSee('Eigener Host-Besuch')
            ->assertSee('Eigener Vertretungs-Besuch')
            ->assertDontSee('Fremder Besuch');
    }

    public function test_visit_user_relations_are_semantically_separate(): void
    {
        $host = User::factory()->create();
        $substitute = User::factory()->create();
        $createdBy = User::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
            'substitute_user_id' => $substitute->id,
            'created_by_user_id' => $createdBy->id,
        ])->load(['host', 'substituteUser', 'createdBy']);

        $this->assertTrue($visit->host->is($host));
        $this->assertTrue($visit->substituteUser->is($substitute));
        $this->assertTrue($visit->createdBy->is($createdBy));
    }

    public function test_created_by_does_not_grant_visit_visibility(): void
    {
        $createdBy = (new PermissionHelper)->getUserUser();
        $host = User::factory()->create();
        $substitute = User::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
            'substitute_user_id' => $substitute->id,
            'created_by_user_id' => $createdBy->id,
        ]);

        $this->assertFalse(Visit::query()->visibleTo($createdBy)->whereKey($visit->id)->exists());
        $this->assertFalse($createdBy->can('view', $visit));
    }

    public function test_my_visits_page_hides_participant_id_card_and_action_columns(): void
    {
        $user = (new PermissionHelper)->getUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'title' => 'Eigener Besuch mit Teilnehmenden',
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);

        $visit->visitors()->sync([$visitor->id]);

        $this
            ->actingAs($user)
            ->get(route('portal.my-visits'))
            ->assertOk()
            ->assertSee('Eigener Besuch mit Teilnehmenden')
            ->assertSee('grid-template-columns: minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, 0.95fr);', false)
            ->assertSee(__('Name'))
            ->assertSee(__('Unternehmen'))
            ->assertSee(__('Status'))
            ->assertDontSee(__('Ausweis'))
            ->assertDontSee(__('Aktion'));
    }

    public function test_visit_show_reschedule_requires_end_time(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
            'scheduled_from' => now()->addDay(),
            'scheduled_until' => now()->addDay()->addHour(),
        ]);

        Livewire::actingAs($user)
            ->test(VisitShowPage::class, ['visit' => $visit])
            ->set('scheduledUntil', '')
            ->call('saveSchedule')
            ->assertHasErrors(['scheduledUntil' => 'required']);
    }

    public function test_visit_show_keeps_allowed_actions_for_host(): void
    {
        Carbon::setTestNow('2026-05-24 10:00:00');

        try {
            $user = (new PermissionHelper)->getUserUser();
            $visit = Visit::factory()->create([
                'host_user_id' => $user->id,
                'substitute_user_id' => null,
                'scheduled_from' => now()->addDay(),
                'scheduled_until' => now()->addDay()->addHour(),
            ]);
            $newStart = now()->addDays(2)->format('Y-m-d\TH:i');
            $newEnd = now()->addDays(2)->addHour()->format('Y-m-d\TH:i');

            Livewire::actingAs($user)
                ->test(VisitShowPage::class, ['visit' => $visit])
                ->assertSee(__('Termin verschieben'))
                ->assertSee(__('Aktionen'))
                ->assertSee(__('Bearbeiten'))
                ->assertSee(__('Besuch absagen'))
                ->set('scheduledFrom', $newStart)
                ->set('scheduledUntil', $newEnd)
                ->call('saveSchedule')
                ->assertHasNoErrors()
                ->call('cancelVisit')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'scheduled_from' => '2026-05-26 10:00:00',
                'scheduled_until' => '2026-05-26 11:00:00',
                'status' => 'canceled',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_visit_show_keeps_allowed_actions_for_admin_role(): void
    {
        Carbon::setTestNow('2026-05-24 10:00:00');

        try {
            config(['security.mfa.app_required_roles' => []]);

            $this->artisan('visitorportal:sync-permissions')->assertExitCode(0);

            $admin = User::factory()->create(['email' => 'admin@example.org']);
            $admin->assignRole('admin');
            $visit = Visit::factory()->create([
                'scheduled_from' => now()->addDay(),
                'scheduled_until' => now()->addDay()->addHour(),
            ]);

            $this
                ->actingAs($admin)
                ->get(route('portal.visits.show', $visit))
                ->assertOk()
                ->assertSee(__('Termin verschieben'))
                ->assertSee(__('Aktionen'))
                ->assertSee(__('Bearbeiten'))
                ->assertSee(__('Besuch absagen'));

            Livewire::actingAs($admin)
                ->test(VisitShowPage::class, ['visit' => $visit])
                ->call('cancelVisit')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => 'canceled',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_visit_show_without_sidebar_keeps_main_content_constrained(): void
    {
        $viewer = (new PermissionHelper)->getIndividualUser([
            'ViewSite:Visit',
        ], 'visit-detail-viewer');
        $visit = Visit::factory()->create([
            'site_id' => $viewer->site_id,
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('portal.visits.show', $visit))
            ->assertOk()
            ->assertSee('data-testid="visit-detail-grid"', false)
            ->assertSee('xl:grid-cols-3', false)
            ->assertSee('data-testid="visit-detail-content"', false)
            ->assertSee('xl:col-span-2', false)
            ->assertDontSee(__('Termin verschieben'))
            ->assertDontSee(__('Aktionen'))
            ->assertDontSee(__('Termin absagen'));
    }
}
