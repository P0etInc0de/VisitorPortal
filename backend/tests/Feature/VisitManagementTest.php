<?php

namespace Tests\Feature;

use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_create_page_defaults_status_to_planned(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('option value="planned" selected', false);
    }

    public function test_visit_create_page_shows_full_user_names_for_host_and_substitute(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->update([
            'first_name' => 'User',
            'name' => 'Admin',
        ]);

        $substitute = (new PermissionHelper)->getUser();
        $substitute->update([
            'first_name' => 'Clara',
            'name' => 'Bergmann',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('User Admin')
            ->assertSee('Clara Bergmann');

        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.$user->id.'"[^>]*selected[^>]*>/i',
            $response->getContent()
        );
    }

    public function test_visit_can_be_created_with_multiple_participants(): void
    {
        $user = (new PermissionHelper)->getUser();

        $responsible = (new PermissionHelper)->getUser();

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
                        'name' => 'Becker',
                        'email' => 'anna@example.com',
                        'phone' => '1234567',
                        'company' => 'ACME AG',
                    ],
                    [
                        'first_name' => 'Max',
                        'name' => 'Mustermann',
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
