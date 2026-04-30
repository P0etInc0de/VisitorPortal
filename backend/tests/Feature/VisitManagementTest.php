<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
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
        $user = (new PermissionHelper)->getReceptionistUser();
        $user->update([
            'first_name' => 'Rita',
            'name' => 'Empfang',
        ]);

        $substitute = User::factory()->create([
            'first_name' => 'Clara',
            'name' => 'Bergmann',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('Rita Empfang')
            ->assertSee('Clara Bergmann');

        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.$user->id.'"[^>]*selected[^>]*>/i',
            $response->getContent()
        );
    }

    public function test_visit_create_page_limits_regular_users_to_themselves_as_host(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->update([
            'first_name' => 'Eigener',
            'name' => 'Benutzer',
        ]);

        $otherUser = User::factory()->create([
            'first_name' => 'Fremder',
            'name' => 'Benutzer',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('Eigener Benutzer')
            ->assertDontSee('Fremder Benutzer');

        $this->assertMatchesRegularExpression(
            '/<option\s+value="'.$user->id.'"[^>]*selected[^>]*>/i',
            $response->getContent()
        );
    }

    public function test_visit_create_page_allows_manager_to_select_users_from_own_department_only(): void
    {
        $department = Department::query()->create([
            'name' => 'Vertrieb',
            'location' => 'A',
            'is_active' => true,
        ]);

        $otherDepartment = Department::query()->create([
            'name' => 'IT',
            'location' => 'B',
            'is_active' => true,
        ]);

        $manager = (new PermissionHelper)->getManagerUser();
        $manager->update([
            'first_name' => 'Mona',
            'name' => 'Manager',
            'department_id' => $department->id,
        ]);

        $sameDepartmentUser = User::factory()->create([
            'first_name' => 'Sina',
            'name' => 'Vertrieb',
            'department_id' => $department->id,
        ]);

        $otherDepartmentUser = User::factory()->create([
            'first_name' => 'Otto',
            'name' => 'IT',
            'department_id' => $otherDepartment->id,
        ]);

        $this
            ->actingAs($manager)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('Mona Manager')
            ->assertSee('Sina Vertrieb')
            ->assertDontSee('Otto IT');
    }

    public function test_visit_create_page_allows_reception_to_select_any_user(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $receptionist->update([
            'first_name' => 'Rita',
            'name' => 'Empfang',
        ]);

        $firstUser = User::factory()->create([
            'first_name' => 'Alpha',
            'name' => 'Eins',
        ]);

        $secondUser = User::factory()->create([
            'first_name' => 'Beta',
            'name' => 'Zwei',
        ]);

        $this
            ->actingAs($receptionist)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertSee('Rita Empfang')
            ->assertSee('Alpha Eins')
            ->assertSee('Beta Zwei');
    }

    public function test_regular_user_cannot_create_visit_for_another_host(): void
    {
        $user = (new PermissionHelper)->getUser();
        $responsible = $user;
        $foreignHost = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('portal.visits.create'))
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'user_id' => $foreignHost->id,
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
                ],
            ]);

        $response
            ->assertRedirect(route('portal.visits.create'))
            ->assertSessionHasErrors(['user_id']);
    }

    public function test_visit_can_be_created_with_multiple_participants(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Technik-Rundgang',
                'user_id' => $user->id,
                'responsible_user_id' => $user->id,
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
