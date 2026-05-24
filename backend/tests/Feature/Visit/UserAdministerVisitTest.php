<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Visit;

use App\Enums\VisitStatusEnum;
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

        $substituteUser = (new PermissionHelper)->getUser();

        $visitors = Visitor::factory(3)->create();

        $visitTitle = 'Test Visit';

        // create the visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $substituteUser->id, $visitors);

        // check if visit is present in database
        $this->assertDatabaseHas('visits', [
            'title' => $visitTitle,
            'substitute_user_id' => $substituteUser->id,
        ]);

    }

    public function test_user_can_cancel_visit(): void
    {

        // create necessary instances
        $visitTitle = 'Test Visit';

        $user = (new PermissionHelper)->getUser();

        $substituteUser = (new PermissionHelper)->getUser();

        // create visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $substituteUser->id);

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

        $user = (new PermissionHelper)->getUser();
        $participant = Visitor::Factory()->create(['created_by_user_id' => $user->id]);

        $substituteUser = (new PermissionHelper)->getUser();

        // create updated fields
        $updatedVisitTitle = 'Updated Visit Title';
        $updatedScheduledFrom = '2026-04-29 10:00:00';
        $updatedScheduledUntil = '2026-04-29 11:00:00';
        $updatedVisitNotes = 'Updated Visit Notes';

        // create visit
        $visit = (new VisitHelper)->makeVisit($user->id, $visitTitle, VisitStatusEnum::Planned, $substituteUser->id);

        // send update form
        $response = $this->actingAs($user)
            ->patch(route('portal.visits.update', $visit), [
                'host_user_id' => $user->id,
                'title' => $updatedVisitTitle,
                'scheduled_from' => $updatedScheduledFrom,
                'scheduled_until' => $updatedScheduledUntil,
                'status' => VisitStatusEnum::Planned->value,
                'notes' => $updatedVisitNotes,
                'participants' => [
                    ['visitor_id' => $participant->id],
                ],
            ]);

        $response->assertSessionHasNoErrors();

        // check if database contains updated versions
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => $updatedVisitTitle,
            'scheduled_from' => $updatedScheduledFrom,
            'scheduled_until' => $updatedScheduledUntil,
            'notes' => $updatedVisitNotes,
        ]);
    }
}
