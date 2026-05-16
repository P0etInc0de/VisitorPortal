<?php

namespace AdminPanel;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
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
     *
     * @throws BindingResolutionException
     */
    public function test_admin_can_create_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminName = 'userAdmin';

        // get an admin user with required permissions
        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:User', 'View:User', 'Create:User', 'Update:User'], $adminName);

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        $this->createUser($admin, 'Test', 'User', 'testuser@example.com', 'password', $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();

    }

    /**
     * @throws BindingResolutionException
     */
    public function test_admin_can_update_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'EditAny:User'], 'admin');

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        // create a test user with role of an employee
        $this->createUser($admin, 'Test', 'User', 'testuser@example.com', 'password', $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();

        // update the existing test user
        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()]) // record returns id of the user
            ->assertOk()
            ->fillForm([
                'first_name' => 'test',
                'name' => 'New Test User',
                'email' => 'newTestuser@example.com',
                'password' => 'newPassword',
                'roles' => [$userRole->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'newTestuser@example.com')->first();

        // User updated in database
        $this->assertDatabaseHas('users', [
            'first_name' => 'test',
            'name' => 'New Test User',
            'email' => 'newTestuser@example.com',
        ]);

        // check if password has been updated too
        $this->assertTrue(
            Hash::check('newPassword', $user->password)
        );

        // old user data not present anymore
        $this->assertDatabaseMissing('users', [
            'first_name' => 'test',
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);

    }

    /**
     * @throws BindingResolutionException
     */
    public function test_admin_can_delete_users(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:User', 'View:User', 'EditAny:User',  'Create:User', 'ForceDeleteAny:User', 'Delete:User', 'DeleteAny:User'], 'admin');

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        // create a test user with role of an employee
        $this->createUser($admin, 'Test', 'User', 'testuser@example.com', 'password', $userRole);

        // check if user with the same email is located in database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        $user = User::where('email', 'testuser@example.com')->first();

        // delete the existing test user
        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors();

        // old user data not present anymore
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

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

    public function test_admin_cannot_create_user_without_required_fields(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'first_name',
                'name',
                'email',
                'password',
            ]);
    }

    public function test_admin_cannot_create_user_with_invalid_email(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'email' => 'not-an-email',
                'password' => 'password',
                'roles' => [$userRole->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_admin_cannot_create_user_without_password(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $userRole = Role::firstOrCreate(['name' => 'employee']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'email' => 'mira.sample@example.com',
                'password' => '',
                'roles' => [$userRole->id],
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }
}
