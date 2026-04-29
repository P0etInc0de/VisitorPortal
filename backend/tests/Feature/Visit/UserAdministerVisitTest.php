<?php

namespace Tests\Feature\Visit;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\Support\VisitHelper;
use Tests\TestCase;

class UserAdministerVisitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature test relating to visits
     */
    public function test_user_can_create_visit(): void
    {
        $user = (new PermissionHelper)->getUser();

        $responsibleUser = (new PermissionHelper)->getUser();

        $visitors = Visitor::factory(3)->create();

        $visitTitle = 'Test Visit';

        // create the visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $responsibleUser->id, $visitors);

        // check if visit is present in database
        $this->assertDatabaseHas('visits', [
            'title' => $visitTitle,
            'responsible_user_id' => $responsibleUser->id,
        ]);

    }

    public function test_user_can_cancel_visit(): void
    {

        // create necessary instances
        $visitTitle = 'Test Visit';

        $user = (new PermissionHelper)->getUser();

        $responsibleUser = (new PermissionHelper)->getUser();

        // create visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $responsibleUser->id);

        // cancel the visit
        $this->actingAs($user)->post(route('portal.visits.cancel', $visit));

        // check if visit status is updated to 'canceled'
        $this->assertDatabaseHas('visits', [
            'title' => $visitTitle,
            'status' => VisitStatusEnum::Canceled,
        ]);

    }

    public function test_user_can_edit_visit(): void
    {

        // create necessary instances
        $visitTitle = 'Test Visit';

        $participant = Visitor::Factory()->create();

        $user = (new PermissionHelper)->getUser();

        $responsibleUser = (new PermissionHelper)->getUser();

        // create updated fields
        $updatedVisitTitle = 'Updated Visit Title';
        $updatedScheduledFrom = '2026-04-29 10:00:00';
        $updatedScheduledUntil = '2026-04-29 11:00:00';
        $updatedVisitNotes = 'Updated Visit Notes';

        // create visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $responsibleUser->id);

        // send update form
        $response = $this->actingAs($user)
            ->patch(route('portal.visits.update', $visit), [
                'user_id' => $user->id,
                'responsible_user_id' => $responsibleUser->id,
                'title' => $updatedVisitTitle,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => $updatedVisitNotes,
                'participants' => [$participant],
            ]);

        // check if database contains updated versions
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => $updatedVisitTitle,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'notes' => $updatedVisitNotes,
        ]);
    }

    private function makeVisit(
        int $user_id,
        string $title,
        VisitStatusEnum $status,
        int $responsible_user_id,
        ?Collection $visitors = null
    ): Visit {

        // create a visit
        $visit = Visit::factory()->create([
            'title' => $title,
            'status' => $status,
            'responsible_user_id' => $responsible_user_id,
            'user_id' => $user_id,
        ]);

        // check if visitors parameter is passed and add them, if so
        if ($visitors && $visitors->isNotEmpty()) {
            $visit->visitors()->sync($visitors->pluck('id')->toArray());
        }

        return $visit;
    }
}
