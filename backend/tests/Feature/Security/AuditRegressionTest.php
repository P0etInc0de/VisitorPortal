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
}
