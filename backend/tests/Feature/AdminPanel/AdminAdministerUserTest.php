<?php

namespace AdminPanel;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerUserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Feature test for testing the Admin area
     * @throws BindingResolutionException
     */
    public function test_admin_can_create_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminName = "userAdmin";

        // get an admin user with required permissions
        $admin = (new PermissionHelper())->getIndividualUser(['ViewAny:User', 'View:User','Create:User', 'Update:User'], $adminName);

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        $this->createUser($admin, "Test User", "testuser@example.com", "password", $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();

        // check if user in database has the same role
        $this->assertTrue($user->hasRole($userRole));

    }

    /**
     * @throws BindingResolutionException
     */
    public function test_admin_can_update_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper())->getIndividualUser(['ViewAny:User', 'View:User','Create:User', 'Update:User'], "admin");

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        // create a test user with role of an employee
        $this->createUser($admin, "Test User", "testuser@example.com", "password", $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();

        // check if user in database has the same role
        $this->assertTrue($user->hasRole($userRole));

        // update the existing test user
        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Users\Pages\EditUser::class, ['record' => $user->getRouteKey()]) // record returns id of the user
            ->assertOk()
            ->fillForm([
                'name' => 'New Test User',
                'email' => 'newTestuser@example.com',
                'password' => 'newPassword',
                'roles' => [$userRole->id]
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'newTestuser@example.com')->first();

        // User updated in database
        $this->assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newTestuser@example.com',
        ]);

        // check if password has been updated too
        $this->assertTrue(
            Hash::check('newPassword', $user->password)
        );

        // old user data not present anymore
        $this->assertDatabaseMissing('users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);

    }

    /**
     * @throws BindingResolutionException
     */
    function test_admin_can_delete_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper())->getIndividualUser(['ViewAny:User', 'View:User', 'Create:User','ForceDeleteAny:User', 'Delete:User', 'DeleteAny:User'], "admin");

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        // create a test user with role of an employee
        $this->createUser($admin, "Test User", "testuser@example.com", "password", $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();


        // delete the existing test user
        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Users\Pages\EditUser::class, ['record' => $user->getRouteKey()])
            ->assertOk()
            ->call('delete')
            ->assertHasNoErrors();


        // old user data not present anymore
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

    }

    function createUser(User $admin, string $name, string $email, string $password, Role $userRole): void
    {
        // create a test user with role of an employee
        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'roles' => [$userRole->id]
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

}
