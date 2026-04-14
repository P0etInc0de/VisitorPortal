<?php

namespace Tests\Feature\Visit;

use App\Enums\VisitStatusEnum;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Visits\Pages\CreateVisit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministerVisitTest extends TestCase
{
    /**
     * Feature test relating to visits
     */
    public function test_user_can_create_visit(): void
    {
        $user = User::factory()->create();

        $responsibleUser = User::factory()->create();

        $visitors = User::factory(4)->create();

        Livewire::actingAs($user)
            ->test(CreateVisit::class)
            ->assertOk()
            ->fillForm([
                'user_id' => $user->id,
                'responsible_user_id' => $responsibleUser->id,
                'scheduled_from' => now()->addDay()->toDateTimeString(),
                'scheduled_until' => now()->addDays(2)->toDateTimeString(),
                'status' => VisitStatusEnum::Planned->value,
                'notes' => 'Test visit notes',
                'canceled_at' => null,
                'canceledBy' => null,
                'visitors' => $visitors->pluck('id')->toArray(),
            ])
            ->call('create')
            ->assertHasNoErrors();

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
