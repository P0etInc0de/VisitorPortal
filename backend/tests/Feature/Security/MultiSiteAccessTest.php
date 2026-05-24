<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Security;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\Monitor;
use App\Models\RecurringVisitSeries;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class MultiSiteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_primary_site_is_synced_to_assigned_sites(): void
    {
        $primarySite = Site::factory()->create();
        $secondarySite = Site::factory()->create();
        $newPrimarySite = Site::factory()->create();

        $user = User::factory()->create(['site_id' => $primarySite->id]);

        $this->assertDatabaseHas('site_user', [
            'user_id' => $user->id,
            'site_id' => $primarySite->id,
        ]);

        $user->sites()->sync([$secondarySite->id]);
        $user->forceFill(['site_id' => $newPrimarySite->id])->save();

        $this->assertDatabaseHas('site_user', [
            'user_id' => $user->id,
            'site_id' => $newPrimarySite->id,
        ]);
        $this->assertEqualsCanonicalizing(
            [$secondarySite->id, $newPrimarySite->id],
            $user->fresh()->assignedSiteIds()->all(),
        );
    }

    public function test_site_scoped_visibility_uses_all_assigned_sites(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $actor = (new PermissionHelper)->getIndividualUser([
            'ViewSite:Visit',
            'ViewSite:Visitor',
        ], 'multi-site-viewer');
        $assignedHost = User::factory()->create(['site_id' => $assignedSite->id]);
        $otherHost = User::factory()->create(['site_id' => $otherSite->id]);
        $visibleVisitor = Visitor::factory()->create(['first_name' => 'Assigned', 'name' => 'Guest']);
        $hiddenVisitor = Visitor::factory()->create(['first_name' => 'Foreign', 'name' => 'Guest']);

        $actor->forceFill(['site_id' => $primarySite->id])->save();
        $actor->sites()->syncWithoutDetaching([$assignedSite->id]);

        $visibleVisit = Visit::factory()->create([
            'site_id' => $assignedSite->id,
            'host_user_id' => $assignedHost->id,
            'substitute_user_id' => null,
        ]);
        $hiddenVisit = Visit::factory()->create([
            'site_id' => $otherSite->id,
            'host_user_id' => $otherHost->id,
            'substitute_user_id' => null,
        ]);
        $visibleVisit->visitors()->attach($visibleVisitor->id);
        $hiddenVisit->visitors()->attach($hiddenVisitor->id);

        $this->assertTrue($actor->can('view', $visibleVisit));
        $this->assertFalse($actor->can('view', $hiddenVisit));
        $this->assertTrue(Visit::query()->visibleTo($actor)->whereKey($visibleVisit->id)->exists());
        $this->assertFalse(Visit::query()->visibleTo($actor)->whereKey($hiddenVisit->id)->exists());
        $this->assertTrue(Visitor::query()->visibleTo($actor)->whereKey($visibleVisitor->id)->exists());
        $this->assertFalse(Visitor::query()->visibleTo($actor)->whereKey($hiddenVisitor->id)->exists());
    }

    public function test_visit_creation_allows_hosts_from_assigned_sites_only(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $actor = (new PermissionHelper)->getReceptionistUser();
        $assignedHost = User::factory()->create([
            'site_id' => $primarySite->id,
            'first_name' => 'Multi',
            'name' => 'Host',
        ]);
        $assignedHost->sites()->syncWithoutDetaching([$assignedSite->id]);
        $otherHost = User::factory()->create([
            'site_id' => $otherSite->id,
            'first_name' => 'Foreign',
            'name' => 'Host',
        ]);

        $actor->forceFill(['site_id' => $primarySite->id])->save();
        $actor->sites()->syncWithoutDetaching([$assignedSite->id]);

        $this->actingAs($actor)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('Multi Host')
            ->assertDontSee('Foreign Host');

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Allowed assigned site visit',
                'site_id' => $assignedSite->id,
                'host_user_id' => $assignedHost->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Allowed',
                        'name' => 'Visitor',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'title' => 'Allowed assigned site visit',
            'site_id' => $assignedSite->id,
            'host_user_id' => $assignedHost->id,
        ]);

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked foreign site visit',
                'site_id' => $assignedSite->id,
                'host_user_id' => $otherHost->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Blocked',
                        'name' => 'Visitor',
                    ],
                ],
            ])
            ->assertSessionHasErrors('host_user_id');

        $this->assertDatabaseMissing('visits', [
            'title' => 'Blocked foreign site visit',
        ]);
    }

    public function test_visit_creation_rejects_inactive_site(): void
    {
        $activeSite = Site::factory()->create();
        $inactiveSite = Site::factory()->create(['is_active' => false]);
        $actor = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create(['site_id' => $activeSite->id]);

        $actor->forceFill(['site_id' => $activeSite->id])->save();
        $actor->sites()->syncWithoutDetaching([$inactiveSite->id]);
        $host->sites()->syncWithoutDetaching([$inactiveSite->id]);

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Inactive site visit',
                'site_id' => $inactiveSite->id,
                'host_user_id' => $host->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Inactive',
                        'name' => 'Visitor',
                    ],
                ],
            ])
            ->assertSessionHasErrors('site_id');

        $this->assertDatabaseMissing('visits', [
            'title' => 'Inactive site visit',
        ]);
    }

    public function test_reception_board_uses_all_assigned_sites(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 16, 10, 0, 0, config('app.timezone')));

        try {
            $primarySite = Site::factory()->create();
            $assignedSite = Site::factory()->create();
            $otherSite = Site::factory()->create();
            $receptionist = (new PermissionHelper)->getReceptionistUser();
            $assignedHost = User::factory()->create(['site_id' => $assignedSite->id]);
            $otherHost = User::factory()->create(['site_id' => $otherSite->id]);
            $visibleVisitor = Visitor::factory()->create(['first_name' => 'Assigned', 'name' => 'Guest']);
            $hiddenVisitor = Visitor::factory()->create(['first_name' => 'Foreign', 'name' => 'Guest']);

            $receptionist->forceFill(['site_id' => $primarySite->id])->save();
            $receptionist->sites()->syncWithoutDetaching([$assignedSite->id]);

            $visibleVisit = Visit::factory()->create([
                'title' => 'Assigned Site Visit',
                'site_id' => $assignedSite->id,
                'host_user_id' => $assignedHost->id,
                'substitute_user_id' => null,
                'scheduled_from' => now()->addHour(),
                'scheduled_until' => now()->addHours(2),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $hiddenVisit = Visit::factory()->create([
                'title' => 'Foreign Site Visit',
                'site_id' => $otherSite->id,
                'host_user_id' => $otherHost->id,
                'substitute_user_id' => null,
                'scheduled_from' => now()->addHour(),
                'scheduled_until' => now()->addHours(2),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $visibleVisit->visitors()->attach($visibleVisitor->id);
            $hiddenVisit->visitors()->attach($hiddenVisitor->id);

            Livewire::actingAs($receptionist)
                ->test(CheckInOutBoard::class)
                ->set('search', 'Guest')
                ->assertSee('Assigned Site Visit')
                ->assertSee('Assigned Guest')
                ->assertDontSee('Foreign Site Visit')
                ->assertDontSee('Foreign Guest');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_walk_in_uses_selected_site_for_multisite_host(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create(['site_id' => $primarySite->id]);

        $receptionist->forceFill(['site_id' => $primarySite->id])->save();
        $receptionist->sites()->syncWithoutDetaching([$assignedSite->id]);
        $host->sites()->syncWithoutDetaching([$assignedSite->id]);

        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->set('walkInSiteId', (string) $assignedSite->id)
            ->set('walkInHostId', (string) $host->id)
            ->set('walkIn.first_name', 'Walk')
            ->set('walkIn.name', 'Assigned')
            ->call('registerWalkIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visits', [
            'site_id' => $assignedSite->id,
            'host_user_id' => $host->id,
            'is_walk_in' => true,
        ]);
    }

    public function test_walk_in_rejects_inactive_site(): void
    {
        $activeSite = Site::factory()->create();
        $inactiveSite = Site::factory()->create(['is_active' => false]);
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create(['site_id' => $activeSite->id]);

        $receptionist->forceFill(['site_id' => $activeSite->id])->save();
        $receptionist->sites()->syncWithoutDetaching([$inactiveSite->id]);
        $host->sites()->syncWithoutDetaching([$inactiveSite->id]);

        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->set('walkInSiteId', (string) $inactiveSite->id)
            ->set('walkInHostId', (string) $host->id)
            ->set('walkIn.first_name', 'Walk')
            ->set('walkIn.name', 'Inactive')
            ->call('registerWalkIn')
            ->assertHasErrors(['walkInSiteId']);

        $this->assertDatabaseMissing('visits', [
            'title' => 'Walk-in: Walk Inactive',
        ]);
    }

    public function test_recurring_visit_series_uses_selected_site_for_multisite_host(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $actor = (new PermissionHelper)->getReceptionistUser();
        $host = User::factory()->create(['site_id' => $primarySite->id]);

        $actor->forceFill(['site_id' => $assignedSite->id])->save();
        $host->sites()->syncWithoutDetaching([$assignedSite->id]);

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Recurring assigned site visit',
                'site_id' => $assignedSite->id,
                'host_user_id' => $host->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'recurrence_enabled' => '1',
                'recurrence_frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
                'recurrence_end_type' => RecurringVisitSeries::END_COUNT,
                'recurrence_occurrence_count' => 2,
                'participants' => [
                    [
                        'first_name' => 'Recurring',
                        'name' => 'Visitor',
                    ],
                ],
            ])
            ->assertRedirect();

        $series = RecurringVisitSeries::query()->where('title', 'Recurring assigned site visit')->sole();

        $this->assertSame($assignedSite->id, $series->site_id);
        $this->assertTrue($series->visits()->where('site_id', $assignedSite->id)->exists());
        $this->assertFalse($series->visits()->where('site_id', $primarySite->id)->exists());
    }

    public function test_visit_update_keeps_selected_site_when_host_primary_site_differs(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $actor = (new PermissionHelper)->getIndividualUser([
            'ViewSite:Visit',
            'EditSite:Visit',
            'ViewSite:Visitor',
        ], 'multi-site-visit-editor');
        $originalHost = User::factory()->create(['site_id' => $assignedSite->id]);
        $newHost = User::factory()->create(['site_id' => $primarySite->id]);
        $visitor = Visitor::factory()->create();

        $actor->forceFill(['site_id' => $primarySite->id])->save();
        $actor->sites()->syncWithoutDetaching([$assignedSite->id]);
        $newHost->sites()->syncWithoutDetaching([$assignedSite->id]);

        $visit = Visit::factory()->create([
            'title' => 'Assigned site update',
            'site_id' => $assignedSite->id,
            'host_user_id' => $originalHost->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visit->visitors()->attach($visitor->id);

        $this->actingAs($actor)
            ->patch(route('portal.visits.update', $visit), [
                'title' => 'Assigned site update changed',
                'site_id' => $assignedSite->id,
                'host_user_id' => $newHost->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertRedirect();

        $visit->refresh();

        $this->assertSame($assignedSite->id, $visit->site_id);
        $this->assertSame($newHost->id, $visit->host_user_id);
    }

    public function test_visit_edit_keeps_current_inactive_site_selectable(): void
    {
        $activeSite = Site::factory()->create(['name' => 'Active Alpha']);
        $otherActiveSite = Site::factory()->create(['name' => 'Active Beta']);
        $currentInactiveSite = Site::factory()->create([
            'name' => 'Inactive Current',
            'is_active' => false,
        ]);
        $otherInactiveSite = Site::factory()->create([
            'name' => 'Inactive Other',
            'is_active' => false,
        ]);
        $actor = (new PermissionHelper)->getIndividualUser([
            'EditAny:Visit',
            'ViewAny:Visit',
        ], 'global-visit-editor');
        $host = User::factory()->create(['site_id' => $currentInactiveSite->id]);

        $actor->forceFill(['site_id' => $activeSite->id])->save();

        $visit = Visit::factory()->create([
            'site_id' => $currentInactiveSite->id,
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Planned->value,
        ]);

        $response = $this->actingAs($actor)
            ->get(route('portal.visits.edit', $visit))
            ->assertOk()
            ->assertSee('Active Alpha')
            ->assertSee('Active Beta')
            ->assertSee('Inactive Current')
            ->assertDontSee('Inactive Other');

        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.$currentInactiveSite->id.'"\s+selected>\s*Inactive Current\s*<\/option>/i',
            $response->getContent()
        );

        $this->assertFalse($otherInactiveSite->is_active);
    }

    public function test_welcome_monitor_redirect_uses_assigned_site_monitor(): void
    {
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $user = (new PermissionHelper)->getWelcomeMonitorUser();
        $monitor = Monitor::query()->create([
            'site_id' => $assignedSite->id,
            'name' => 'Assigned Site Monitor',
        ]);

        $user->forceFill(['site_id' => $primarySite->id])->save();
        $user->sites()->syncWithoutDetaching([$assignedSite->id]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('monitors.show', $monitor, absolute: false));
    }
}
