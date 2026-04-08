<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_can_be_created_with_multiple_participants(): void
    {
        $user = User::factory()->create();
        $responsible = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'user_id' => $user->id,
                'responsible_user_id' => $responsible->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'notes' => 'Empfang briefen',
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'last_name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '1234567',
                        'company' => 'ACME AG',
                    ],
                    [
                        'first_name' => 'Max',
                        'last_name' => 'Mustermann',
                        'email' => 'max@example.com',
                        'phone' => '7654321',
                        'company' => 'Beispiel GmbH',
                    ],
                ],
            ]);

        $visit = Visit::query()->firstOrFail();

        $response->assertRedirect(route('portal.visits.show', $visit));

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'title' => 'Technik-Rundgang',
        ]);

        $this->assertDatabaseCount('visitors', 2);
        $this->assertDatabaseCount('visit_visitor', 2);
    }
}
