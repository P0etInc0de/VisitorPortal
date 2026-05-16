<?php

namespace Tests\Feature\Security;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\Monitor;
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
            'user_id' => $assignedHost->id,
            'responsible_user_id' => $assignedHost->id,
        ]);
        $hiddenVisit = Visit::factory()->create([
            'site_id' => $otherSite->id,
            'user_id' => $otherHost->id,
            'responsible_user_id' => $otherHost->id,
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
            'site_id' => $assignedSite->id,
            'first_name' => 'Assigned',
            'name' => 'Host',
        ]);
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
            ->assertSee('Assigned Host')
            ->assertDontSee('Foreign Host');

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Allowed assigned site visit',
                'user_id' => $assignedHost->id,
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
            'user_id' => $assignedHost->id,
        ]);

        $this->actingAs($actor)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked foreign site visit',
                'user_id' => $otherHost->id,
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
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('visits', [
            'title' => 'Blocked foreign site visit',
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
                'user_id' => $assignedHost->id,
                'responsible_user_id' => $assignedHost->id,
                'scheduled_from' => now()->addHour(),
                'scheduled_until' => now()->addHours(2),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $hiddenVisit = Visit::factory()->create([
                'title' => 'Foreign Site Visit',
                'site_id' => $otherSite->id,
                'user_id' => $otherHost->id,
                'responsible_user_id' => $otherHost->id,
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
