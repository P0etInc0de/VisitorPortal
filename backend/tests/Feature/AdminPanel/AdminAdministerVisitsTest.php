<?php

namespace AdminPanel;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use App\Enums\VisitStatusEnum;
use App\Filament\Resources\Visitors\Pages\CreateVisitor;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Filament\Resources\Visits\Pages\EditVisit;
use App\Http\Requests\UpdateVisitRequest;
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
     * A basic feature test example.
     */
    public function test_admin_can_create_new_visits(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');



        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $employee->id,
                'title' => 'Test Title',
                'responsible_user_id' => $responsibleUser->id,
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
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
            'scheduled_from' => $scheduledFrom,
            'scheduled_until' => $scheduledUntil,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);
    }
    public function test_admin_can_edit_existing_visits(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $employee->id,
                'title' => 'Test Title',
                'responsible_user_id' => $responsibleUser->id,
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
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,

        ]);

        // ensure to get the correct visit
        $visit = Visit::where([
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
        ])->latest()->first();

        foreach($visitors as $visitor) {
            $this->assertDatabaseHas('visit_visitor', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
            ]);
        }



        // create updated values
        $updatedUserId = User::factory()->create()->id;
        $updatedTitle = 'New Test Title';
        $updatedResponsibleUserId = User::factory()->create()->id;
        $updatedScheduledFrom = now()->addDays(2)->format('Y-m-d H:i:00');;
        $updatedScheduledUntil = now()->addDays(3)->format('Y-m-d H:i:00');;
        $updatedStatus = VisitStatusEnum::Completed->value;
        $updatedNotes = 'Updated Visit';
        $updatedVisitors = Visitor::factory()->count(2)->create()->pluck('id')->toArray();

        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->fillForm([
                'user_id' => $updatedUserId,
                'title' => $updatedTitle,
                'responsible_user_id' => $updatedResponsibleUserId,
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
            'user_id' => $updatedUserId,
            'title' => $updatedTitle,
            'responsible_user_id' => $updatedResponsibleUserId,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'status' => $updatedStatus,
            'notes' => $updatedNotes,
        ]);
    }
    public function test_admin_can_delete_visits(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:00');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:00');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $employee->id,
                'title' => 'Test Title',
                'responsible_user_id' => $responsibleUser->id,
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
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,

        ]);

        // ensure to get the correct visit
        $visit = Visit::where([
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
        ])->latest()->first();

        // check if pivot is filled accordingly
        foreach($visitors as $visitor) {
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
            'user_id' => $employee->id,
            'title' => 'Test Title',
            'responsible_user_id' => $responsibleUser->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);
    }
}
