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

        $visitors = Visit::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:s');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:s');


        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $admin->id,
                'responsible_user_id' => $responsibleUser->id,
                'scheduled_from' => $scheduledFrom,
                'scheduled_until' => $scheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit',
                'canceled_at' => null,
                'canceledBy' => null,
                'visitors' => $visitors->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'user_id' => $employee->id,
            'responsible_user_id' => $responsibleUser->id,
            'scheduled_from' => $scheduledFrom,
            'scheduled_until' => $scheduledUntil,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceledBy' => null,
            'visitors' => $visitors->pluck('id')->toArray(),
        ]);
    }
    public function test_admin_can_edit_existing_visits(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $employee = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = Visit::factory()->count(3)->create();

        $scheduledFrom = now()->addDay()->format('Y-m-d H:i:s');
        $scheduledUntil = now()->addDays(2)->format('Y-m-d H:i:s');

        Livewire::actingAs($admin)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $employee->id,
                'responsible_user_id' => $responsibleUser->id,
                'scheduled_from' => $scheduledFrom,
                'scheduled_until' => $scheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit',
                'canceled_at' => null,
                'canceledBy' => null,
                'visitors' => $visitors->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'user_id' => $employee->id,
            'responsible_user_id' => $responsibleUser->id,
            'scheduled_from' => $scheduledFrom,
            'scheduled_until' => $scheduledUntil,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Test Visit',
            'canceled_at' => null,
            'canceledBy' => null,
            'visitors' => $visitors->pluck('id')->toArray(),
        ]);

        // ensure to get the correct visit
        $visit = Visit::where([
            'user_id' => $employee->id,
            'responsible_user_id' => $responsibleUser->id,
            'scheduled_from' => $scheduledFrom,
            'scheduled_until' => $scheduledUntil,
        ])->latest()->first();

        // create updated values
        $updatedUserId = User::factory()->create()->id;
        $updatedResponsibleUserId = User::factory()->create()->id;
        $updatedScheduledFrom = now()->addDays(2);
        $updatedScheduledUntil = now()->addDays(3);
        $updatedStatus = VisitStatusEnum::Completed->value;
        $updatedNotes = 'Updated Visit';
        $updatedCanceledAt = now();
        $updatedCanceledBy = $admin->id;
        $updatedVisitors = User::factory()->count(2)->create()->pluck('id')->toArray();

        Livewire::actingAs($admin)
            ->test(EditVisit::class, ['record' => $visit->id])
            ->assertOk()
            ->fillForm([
                'user_id' => $updatedUserId,
                'responsible_user_id' => $updatedResponsibleUserId,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => $updatedStatus,
                'notes' => $updatedNotes,
                'canceled_at' => $updatedCanceledAt,
                'canceledBy' => $updatedCanceledBy,
                'visitors' => $updatedVisitors,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visits', [
            'user_id' => $updatedUserId,
            'responsible_user_id' => $updatedResponsibleUserId,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'status' => $updatedStatus,
            'notes' => $updatedNotes,
            'canceled_at' => $updatedCanceledAt,
            'canceledBy' => $updatedCanceledBy,
            'visitors' => $updatedVisitors,
        ]);
    }
    public function test_admin_can_delete_visits(): void {}
}
