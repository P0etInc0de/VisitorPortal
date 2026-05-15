<?php

namespace Tests\Feature\Security;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\Monitor;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_open_foreign_visit_show_page(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visit = Visit::factory()->create([
            'user_id' => $owner->id,
            'responsible_user_id' => $owner->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.visits.show', $visit))
            ->assertForbidden();
    }

    public function test_department_scoped_user_without_department_cannot_view_unassigned_department_visit(): void
    {
        $manager = (new PermissionHelper)->getManagerUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visit = Visit::factory()->create([
            'user_id' => $owner->id,
            'responsible_user_id' => $owner->id,
        ]);

        $this->assertNull($manager->department_id);
        $this->assertNull($visit->host?->department_id);

        $this->actingAs($manager)
            ->get(route('portal.visits.show', $visit))
            ->assertForbidden();
    }

    public function test_department_scoped_user_without_department_cannot_view_unassigned_department_user(): void
    {
        $manager = (new PermissionHelper)->getManagerUser();
        $otherUser = (new PermissionHelper)->getUserUser();

        $this->assertNull($manager->department_id);
        $this->assertNull($otherUser->department_id);
        $this->assertFalse($manager->can('view', $otherUser));
    }

    public function test_user_cannot_patch_foreign_visit(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'user_id' => $owner->id,
            'responsible_user_id' => $owner->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visit->visitors()->attach($visitor->id);

        $this->actingAs($user)
            ->patch(route('portal.visits.update', $visit), [
                'title' => 'Blocked Update',
                'user_id' => $owner->id,
                'responsible_user_id' => $owner->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_regular_user_create_page_does_not_expose_unrelated_visitor_directory(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        Visitor::factory()->create([
            'first_name' => 'Hidden',
            'name' => 'Visitor',
            'email' => 'hidden.visitor@example.com',
            'phone' => '+49 111 222333',
        ]);

        $this->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertDontSee('Hidden Visitor')
            ->assertDontSee('hidden.visitor@example.com')
            ->assertDontSee('+49 111 222333');
    }

    public function test_regular_user_cannot_attach_hidden_visitor_by_id(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Visitor Attach',
                'user_id' => $user->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertSessionHasErrors('participants.0.visitor_id');
    }

    public function test_regular_user_cannot_reuse_hidden_visitor_by_contact_details(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Hidden',
            'name' => 'Visitor',
            'email' => 'hidden.contact@example.com',
            'phone' => '+49 444 555666',
        ]);

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Contact Reuse',
                'user_id' => $user->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Attempted',
                        'name' => 'Overwrite',
                        'email' => 'hidden.contact@example.com',
                    ],
                ],
            ])
            ->assertSessionHasErrors('participants');

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'first_name' => 'Hidden',
            'name' => 'Visitor',
        ]);
    }

    public function test_regular_user_cannot_create_visit_for_another_host_or_completed_status(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $otherUser = (new PermissionHelper)->getUserUser();

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Host Status',
                'user_id' => $otherUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Completed->value,
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                    ],
                ],
            ])
            ->assertSessionHasErrors(['user_id', 'status']);
    }

    public function test_regular_user_cannot_mount_reception_check_in_board(): void
    {
        $user = (new PermissionHelper)->getUserUser();

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertForbidden();
    }

    public function test_regular_user_cannot_call_reception_check_in_route(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'user_id' => $owner->id,
            'responsible_user_id' => $owner->id,
        ]);
        $visit->visitors()->attach($visitor->id);

        $this->actingAs($user)
            ->post(route('reception.participants.check-in', [$visit, $visitor]))
            ->assertForbidden();
    }

    public function test_monitor_slide_cannot_be_deleted_through_wrong_monitor_parent(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $monitor = Monitor::query()->create(['name' => 'Monitor A', 'auto_generation' => false]);
        $otherMonitor = Monitor::query()->create(['name' => 'Monitor B', 'auto_generation' => false]);
        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Scoped Slide',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('monitors.slides.destroy', [$otherMonitor, $slide]))
            ->assertNotFound();

        $this->assertDatabaseHas('monitor_slides', ['id' => $slide->id]);
    }

    public function test_retention_cleans_monitor_slide_visitor_payloads(): void
    {
        $oldTimestamp = now()->subDays(400);
        $monitor = Monitor::query()->create(['name' => 'Monitor A', 'auto_generation' => false]);
        $visitor = Visitor::factory()->create([
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);
        $autoSlide = $monitor->monitorSlides()->create([
            'heading' => 'Auto',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => true,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => null, 'name' => 'Old Auto Visitor']],
        ]);
        $manualSlide = $monitor->monitorSlides()->create([
            'heading' => 'Manual',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => null, 'name' => 'Old Manual Visitor']],
        ]);
        $recentSlide = $monitor->monitorSlides()->create([
            'heading' => 'Recent',
            'slide_number' => 2,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => $visitor->id, 'name' => 'Expired Visitor']],
        ]);

        $autoSlide->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();
        $manualSlide->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();

        $this->artisan('visits:purge-expired', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('monitor_slides', ['id' => $autoSlide->id]);
        $this->assertNull($manualSlide->fresh()->visitors);
        $this->assertDatabaseMissing('visitors', ['id' => $visitor->id]);
        $this->assertNull($recentSlide->fresh()->visitors);
    }
}
