<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateVisitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testing if a user can create a new visit
     */
    public function test_user_can_create_visit(): void
    {

        $user = User::factory()->create();

        // data still has to be filled with the right form (not available currently)
        $data = [
            'title' => 'Visit',
        ];

        $response = $this->actingAs($user)->post('/portal/visits/create', $data);

        // check for right response

        // check if data is now correctly stored in database
    }
}
