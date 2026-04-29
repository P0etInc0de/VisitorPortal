<?php

namespace Tests\Feature\Receptionist;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_receptionist_can_check_in_visitors(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $employee = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        $this->assertDatabaseHas('visitors', [
            'email' => $visitors[0]->email,
        ]);

        $responsibleUser = User::factory()->create();
        $visitTitle = 'Test Visit';

        // create visit with status 'Planned'
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            $visitTitle,
            VisitStatusEnum::Planned,
            $responsibleUser->id,
            $visitors
        );

        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->call('checkIn', $visit->id, $visitors[0]->id)
            ->assertStatus(200);

        // check if status is updated in pivot table
        $this->assertDatabaseHas('visit_visitor', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitors[0]->id,
            'checked_in_at' => now(),
        ]);

    }

    public function test_receptionist_can_check_out_visitors(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $employee = User::factory()->create();

        $visitors = Visitor::factory()->count(3)->create();

        // all visitors present in database
        foreach ($visitors as $visitor) {
            $this->assertDatabaseHas('visitors', [
                'email' => $visitor->email,
            ]);
        }

        $responsibleUser = User::factory()->create();
        $visitTitle = 'Test Visit';

        // create visit with status 'Planned'
        $visit = (new VisitHelper)->makeVisit(
            $employee->id,
            $visitTitle,
            VisitStatusEnum::Completed,
            $responsibleUser->id,
            $visitors
        );

        // visitors have to be checked in to be able the check them out
        $this->checkInVisitors($visit, $visitors);

        // perform the checkout
        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->call('checkOut', $visit->id, $visitors[0]->id)
            ->assertStatus(200);

        // check if status is updated in pivot table
        $this->assertDatabaseHas('visit_visitor', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitors[0]->id,
            'checked_out_at' => now(),
        ]);

    }

    public function test_receptionist_can_create_walk_ins(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $host = (new PermissionHelper)->getUserUser();

        $walkInTitle = 'Dr';
        $walkInFirstName = 'Max';
        $walkInLastName = 'Mustermann';
        $walkInEmail = 'max@test.de';
        $walkInPhoneNumber = '0123456789';
        $walkInCompany = 'TestCompany';

        // create a walk-in
        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->set('walkInHostId', $host->id)
            ->set('walkIn.title', $walkInTitle)
            ->set('walkIn.first_name', $walkInFirstName)
            ->set('walkIn.name', $walkInLastName)
            ->set('walkIn.email', $walkInEmail)
            ->set('walkIn.phone', $walkInPhoneNumber)
            ->set('walkIn.company', $walkInCompany)
            ->call('registerWalkIn', false)
            ->assertHasNoErrors();

        // check if walk-in is present in visit table
        $this->assertDatabaseHas('visits', [
            'title' => 'Walk-in: '.$walkInTitle.' '.$walkInFirstName.' '.$walkInLastName,
        ]);
    }

    public function test_reception_can_reschedule_appointments(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $scheduledFromTimeStamp = now()->subDay();
        $scheduledToTimeStamp = now()->subDay()->addMinutes(60);

        // create a visit with timestamps
        $visit = Visit::factory()->create([
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

        // check if visit created in database
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'scheduled_from' => $scheduledFromTimeStamp,
            'scheduled_until' => $scheduledToTimeStamp,
        ]);

        // create new timestamps
        $scheduledFromTimeStampUpdated = now();
        $scheduledToTimeStampUpdated = now()->addMinutes(60);

        // reschedule visit with new timestamps
        $this->actingAs($receptionist)
            ->patch(route('portal.visits.reschedule', $visit), [
                'scheduled_from' => $scheduledFromTimeStampUpdated,
                'scheduled_until' => $scheduledToTimeStampUpdated,
            ]);

        // check if visit has been rescheduled
        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'scheduled_from' => $scheduledFromTimeStampUpdated,
            'scheduled_until' => $scheduledToTimeStampUpdated,
        ]);

    }

    public function test_reception_can_create_badges(): void {}

    public function test_reception_can_view_own_user_permissions(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)
            ->get(route('user-permissions'));

        $response->assertStatus(200);
    }

    public function test_reception_can_view_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)
            ->get(route('reception.dashboard'));

        $response->assertStatus(200);
    }

    public function checkInVisitors(Visit $visit, Collection $visitors): void
    {
        foreach ($visitors as $visitor) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => now(),
            ]);
        }
    }
}
