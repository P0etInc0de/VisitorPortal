<?php

namespace Tests\Feature\Receptionist;

use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
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
            ->get(route('reception.all-visits'));

        $response->assertStatus(200);
    }

    public function test_receptionist_can_create_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
    }

    public function test_receptionist_can_edit_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
    }

    public function test_receptionist_can_cancel_visit(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
    }
}
