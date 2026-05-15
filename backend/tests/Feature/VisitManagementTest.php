<?php

namespace Tests\Feature;

use App\Models\RecurringVisitSeries;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            ->assertSee('option value="planned" selected', false);
    }

    public function test_visit_create_page_shows_full_user_names_for_host_and_substitute(): void
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
            ->assertSee('User Admin')
            ->assertSee('Clara Bergmann');

        $this->assertMatchesRegularExpression(
            '/<input[^>]+name="user_id"[^>]+value="'.$user->id.'"/i',
            $response->getContent()
        );
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
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '1234567',
                        'company' => 'ACME AG',
                    ],
                ],
            ])
            ->assertSessionHasErrors('responsible_user_id');
    }

    public function test_visit_can_be_created_with_multiple_participants(): void
    {
        $user = (new PermissionHelper)->getUser();

        $responsible = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'user_id' => $user->id,
                'responsible_user_id' => $responsible->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'notes' => 'Empfang briefen',
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '1234567',
                        'company' => 'ACME AG',
                    ],
                    [
                        'first_name' => 'Max',
                        'name' => 'Mustermann',
                        'email' => 'max@example.com',
                        'phone' => '7654321',
                        'company' => 'Beispiel GmbH',
                    ],
                ],
            ]);

        $visit = Visit::query()->firstOrFail();

        $response->assertRedirect(route('portal.visits.show', $visit));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => 'Technik-Rundgang',
        ]);

        $this->assertDatabaseCount('visitors', 2);
        $this->assertDatabaseCount('visit_visitor', 2);
    }

    public function test_recurring_visit_can_be_created_with_total_occurrence_count(): void
    {
        $user = (new PermissionHelper)->getUser();
        $responsible = (new PermissionHelper)->getUser();
        $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Wöchentliche Sicherheitsrunde',
                'user_id' => $user->id,
                'responsible_user_id' => $responsible->id,
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
        $responsible = (new PermissionHelper)->getUser();
        $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

        $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Regeltermin',
                'user_id' => $user->id,
                'responsible_user_id' => $responsible->id,
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
                'user_id' => $user->id,
                'responsible_user_id' => $responsible->id,
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
            $responsible = (new PermissionHelper)->getUser();
            $participant = Visitor::factory()->create(['created_by_user_id' => $user->id]);

            $this
                ->actingAs($user)
                ->post(route('portal.visits.store'), [
                    'title' => 'Endlose Wartung',
                    'user_id' => $user->id,
                    'responsible_user_id' => $responsible->id,
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
            'user_id' => $user->id,
            'responsible_user_id' => $otherUser->id,
        ]);

        Visit::factory()->create([
            'title' => 'Eigener Vertretungs-Besuch',
            'user_id' => $otherUser->id,
            'responsible_user_id' => $user->id,
        ]);

        Visit::factory()->create([
            'title' => 'Fremder Besuch',
            'user_id' => $otherUser->id,
            'responsible_user_id' => $otherUser->id,
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

    public function test_my_visits_page_hides_participant_id_card_and_action_columns(): void
    {
        $user = (new PermissionHelper)->getUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'title' => 'Eigener Besuch mit Teilnehmenden',
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
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
}
