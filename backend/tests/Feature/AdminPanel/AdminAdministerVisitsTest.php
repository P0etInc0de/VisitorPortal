<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace AdminPanel;

use App\Enums\VisitStatusEnum;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Filament\Resources\Visits\Pages\EditVisit;
use App\Filament\Resources\Visits\Pages\ViewVisit;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerVisitsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature Tests for the admin, administering visits
     */
    public function test_admin_can_create_new_visits(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $substituteUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'host_user_id' => $employee->id,
                'title' => 'Test Title',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => $scheduledFrom,
                'scheduled_until' => $scheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit',
                'canceled_at' => null,
                'canceled_by_user_id' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
            'scheduled_from' => $scheduledFrom,
            'scheduled_until' => $scheduledUntil,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);
    }

    public function test_admin_cannot_create_visit_with_site_host_mismatch(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $employee = User::factory()->create(['site_id' => $otherSite->id]);
        $substituteUser = User::factory()->create(['site_id' => $otherSite->id]);

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'site_id' => $site->id,
                'host_user_id' => $employee->id,
                'title' => 'Invalid Site Host',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['host_user_id', 'substitute_user_id']);

        $this->assertDatabaseMissing('visits', [
            'title' => 'Invalid Site Host',
        ]);
    }

    public function test_admin_can_create_visit_with_host_assigned_to_selected_site(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $employee = User::factory()->create(['site_id' => $primarySite->id]);
        $substituteUser = User::factory()->create(['site_id' => $primarySite->id]);

        $employee->sites()->syncWithoutDetaching([$assignedSite->id]);
        $substituteUser->sites()->syncWithoutDetaching([$assignedSite->id]);

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'site_id' => $assignedSite->id,
                'host_user_id' => $employee->id,
                'title' => 'Assigned Site Admin Visit',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'title' => 'Assigned Site Admin Visit',
            'site_id' => $assignedSite->id,
            'host_user_id' => $employee->id,
            'substitute_user_id' => $substituteUser->id,
        ]);
    }

    public function test_admin_can_update_visit_with_host_assigned_to_selected_site(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $originalHost = User::factory()->create(['site_id' => $assignedSite->id]);
        $originalSubstitute = User::factory()->create(['site_id' => $assignedSite->id]);
        $employee = User::factory()->create(['site_id' => $primarySite->id]);
        $substituteUser = User::factory()->create(['site_id' => $primarySite->id]);
        $visit = Visit::factory()->create([
            'site_id' => $assignedSite->id,
            'host_user_id' => $originalHost->id,
            'substitute_user_id' => $originalSubstitute->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);

        $employee->sites()->syncWithoutDetaching([$assignedSite->id]);
        $substituteUser->sites()->syncWithoutDetaching([$assignedSite->id]);

        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->fillForm([
                'site_id' => $assignedSite->id,
                'host_user_id' => $employee->id,
                'title' => 'Updated Assigned Site Admin Visit',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $visit->refresh();

        $this->assertSame($assignedSite->id, $visit->site_id);
        $this->assertSame($employee->id, $visit->host_user_id);
        $this->assertSame($substituteUser->id, $visit->substitute_user_id);
    }

    public function test_admin_cannot_create_visit_for_inactive_site(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $inactiveSite = Site::factory()->create(['is_active' => false]);
        $employee = User::factory()->create(['site_id' => $inactiveSite->id]);
        $substituteUser = User::factory()->create(['site_id' => $inactiveSite->id]);

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'site_id' => $inactiveSite->id,
                'host_user_id' => $employee->id,
                'title' => 'Inactive Site Visit',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['site_id']);

        $this->assertDatabaseMissing('visits', [
            'title' => 'Inactive Site Visit',
        ]);
    }

    public function test_admin_can_edit_existing_visits(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $substituteUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'host_user_id' => $employee->id,
                'title' => 'Test Title',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => $scheduledFrom,
                'scheduled_until' => $scheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit',
                'canceled_at' => null,
                'canceled_by_user_id' => null,
                'visitors' => $visitors->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,

        ]);

        // ensure to get the correct visit
        $visit = Visit::where([
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
        ])->latest()->first();

        foreach ($visitors as $visitor) {
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
            ]);
        }

        // create updated values
        $updatedUserId = User::factory()->create()->id;
        $updatedTitle = 'New Test Title';
        $updatedSubstituteUserId = User::factory()->create()->id;
        $updatedScheduledFrom = now()->addDays(2)->format('Y-m-d H:i:00');
        $updatedScheduledUntil = now()->addDays(3)->format('Y-m-d H:i:00');
        $updatedStatus = VisitStatusEnum::Completed->value;
        $updatedNotes = 'Updated Visit';
        $updatedVisitors = Visitor::factory()->count(2)->create()->pluck('id')->toArray();

        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->fillForm([
                'host_user_id' => $updatedUserId,
                'title' => $updatedTitle,
                'substitute_user_id' => $updatedSubstituteUserId,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => $updatedStatus,
                'notes' => $updatedNotes,
                'canceled_at' => null,
                'canceled_by_user_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'host_user_id' => $updatedUserId,
            'title' => $updatedTitle,
            'substitute_user_id' => $updatedSubstituteUserId,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'status' => $updatedStatus,
            'notes' => $updatedNotes,
        ]);
    }

    public function test_admin_can_delete_visits(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $substituteUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'host_user_id' => $employee->id,
                'title' => 'Test Title',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => $scheduledFrom,
                'scheduled_until' => $scheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit',
                'canceled_at' => null,
                'canceled_by_user_id' => null,
                'visitors' => $visitors->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // ensure the visit previously existed
        $this->assertDatabaseHas('visits', [
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,

        ]);

        // ensure to get the correct visit
        $visit = Visit::where([
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
        ])->latest()->first();

        // check if pivot is filled accordingly
        foreach ($visitors as $visitor) {
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
            ]);
        }

        // delete the visit
        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->callAction('delete')
            ->assertHasNoFormErrors();

        // check if visit is missing in db
        $this->assertDatabaseMissing('visits', [
            'host_user_id' => $employee->id,
            'title' => 'Test Title',
            'substitute_user_id' => $substituteUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);
    }

    public function test_admin_can_create_visit_without_visitors(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->fillForm([
                'host_user_id' => $employee->id,
                'title' => 'No Visitors Visit',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDays(2)->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('visit_visitor', 0);
    }

    public function test_admin_visit_infolist_shows_host_substitute_and_created_by(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $host = User::factory()->create(['first_name' => 'Ada', 'name' => 'Avery']);
        $substitute = User::factory()->create(['first_name' => 'Rita', 'name' => 'Reed']);
        $createdBy = User::factory()->create(['first_name' => 'Casey', 'name' => 'Creator']);
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
            'substitute_user_id' => $substitute->id,
            'created_by_user_id' => $createdBy->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ViewVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->assertSee('Ada Avery')
            ->assertSee('Rita Reed')
            ->assertSee(__('Erstellt von'))
            ->assertSee('Casey Creator');
    }

    public function test_admin_cannot_create_visit_without_required_fields(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'title',
                'scheduled_from',
                'scheduled_until',
                'status',
            ]);
    }

    public function test_admin_cannot_attach_duplicate_visitors(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();
        $substituteUser = User::factory()->create();

        $visitor = Visitor::factory()->create();
        $anotherVisitor = Visitor::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->fillForm([
                'host_user_id' => $employee->id,
                'title' => 'Duplicate Visitor Test',
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:00'),
                'scheduled_until' => now()->addDays(2)->format('Y-m-d H:i:00'),
                'status' => VisitStatusEnum::Planned->value,
                'visitors' => [$visitor->id, $visitor->id],
            ])
            ->call('create')
            ->assertHasFormErrors();

    }

    public function test_admin_can_complete_visit(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        // create a planned visit
        $visit = Visit::factory()->create([
            'status' => VisitStatusEnum::Planned->value,
        ]);

        // complete visit
        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->fillForm([
                'status' => VisitStatusEnum::Completed->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatusEnum::Completed->value,
        ]);
    }

    public function test_admin_can_cancel_visit(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // create a planned visit
        $visit = Visit::factory()->create([
            'status' => VisitStatusEnum::Planned->value,
        ]);

        // cancel visit
        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->callAction('cancel')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatusEnum::Canceled->value,
            'canceled_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_restore_visit(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // create a canceled visit
        $visit = Visit::factory()->create([
            'status' => VisitStatusEnum::Canceled->value,
        ]);

        // restore visit
        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->callAction('Restore')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);
    }
}
