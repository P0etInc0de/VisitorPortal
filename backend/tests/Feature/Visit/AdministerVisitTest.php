<?php

namespace Tests\Feature\Visit;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministerVisitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature test relating to visits
     */
    public function test_user_can_create_visit(): void
    {
        $user = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = User::factory(4)->create();

        $response = $this
            ->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'TestVisit',
                'user_id' => $user->id,
                'responsible_user_id' => $responsibleUser->id,
                'scheduled_from' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'status' => 'planned',
                'notes' => 'notes...',
                'participants' => $visitors->pluck('id')->toArray(),
            ]);

        $this->assertDatabaseHas('visits', [
            'title' => 'TestVisit',
            'responsible_user_id' => $responsibleUser->id,
        ]);

    }

    public function getIndividualUser(array $individualPermissions, string $name): User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        foreach ($individualPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // replace old permissions with the new ones
        $role->syncPermissions($individualPermissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function createUser(User $admin, string $first_name, string $name, string $email, string $password, Role $userRole): void
    {
        // create a test user with role of an employee
        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'first_name' => $first_name,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'roles' => [$userRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}
