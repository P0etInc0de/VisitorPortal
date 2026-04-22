<?php

namespace Tests\Feature\Receptionist;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /**
     * @throws \JsonException
     */
    public function test_receptionist_can_create_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $participant = User::factory()->create();

        $visitTitle = 'Test Visit';

        $employee = User::factory()->create();

        $responsibleUser = User::factory()->create();

        // create a visit acting as the receptionist
        $response = $this->actingAs($receptionist)
            ->post(route('portal.visits.store'), [
                'user_id' => $employee->id,
                'responsible_user_id' => $responsibleUser->id,
                'title' => $visitTitle,
                'scheduled_from' => '2026-04-29 10:00:00',
                'scheduled_until' => '2026-04-29 11:00:00',
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test Visit Notes',
                'participants' => [$participant->id],
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
    public function test_receptionist_can_edit_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $participant = User::factory()->create();
        $employee = User::factory()->create();
        $responsibleUser = User::factory()->create();

        // create a visit
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            'Test Visit',
            VisitStatusEnum::Planned,
            $responsibleUser->id
        );

        // updated fields
        $updatedVisitTitle = 'Updated Visit Title';
        $updatedScheduledFrom = '2026-04-29 10:00:00';
        $updatedScheduledUntil = '2026-04-29 11:00:00';
        $updatedVisitNotes = 'Updated Visit Notes';

        // send update request
        $response = $this->actingAs($receptionist)
            ->patch(route('portal.visits.update', $visit), [
                'user_id' => $employee->id,
                'responsible_user_id' => $responsibleUser->id,
                'title' => $updatedVisitTitle,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => $updatedVisitNotes,
                'participants' => [$participant->id],
            ]);

        $response->assertSessionHasNoErrors();

        // check if database contains updated values
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => $updatedVisitTitle,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'notes' => $updatedVisitNotes,
        ]);
    }

    public function test_receptionist_can_cancel_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $employee = User::factory()->create();
        $responsibleUser = User::factory()->create();

        $visitTitle = 'Test Visit';

        // create visit with status 'Planned'
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            $visitTitle,
            VisitStatusEnum::Planned,
            $responsibleUser->id
        );

        // cancel the visit
        $response = $this->actingAs($receptionist)
            ->post(route('portal.visits.cancel', $visit));




        // check if the status is now set to 'Canceled'
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'status' => VisitStatusEnum::Canceled,
        ]);
    }
}
