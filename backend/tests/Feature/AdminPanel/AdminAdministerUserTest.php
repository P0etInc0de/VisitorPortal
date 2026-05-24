<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace AdminPanel;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Site;
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

    public function test_admin_can_assign_user_to_multiple_sites(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:User', 'View:User', 'Create:User', 'Update:User'], 'admin');
        $primarySite = Site::factory()->create();
        $assignedSite = Site::factory()->create();
        $userRole = Role::firstOrCreate(['name' => 'employee']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'site_id' => $primarySite->id,
                'sites' => [$assignedSite->id],
                'first_name' => 'Multi',
                'name' => 'Site User',
                'email' => 'multi.site.user@example.com',
                'password' => 'password',
                'roles' => [$userRole->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'multi.site.user@example.com')->firstOrFail();

        $this->assertDatabaseHas('site_user', [
            'user_id' => $user->id,
            'site_id' => $primarySite->id,
        ]);
        $this->assertDatabaseHas('site_user', [
            'user_id' => $user->id,
            'site_id' => $assignedSite->id,
        ]);
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:User', 'View:User', 'Update:User', 'EditAny:User'], 'admin');
        $user = User::factory()->create([
            'password' => Hash::make('OriginalPassword123!'),
        ]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'first_name' => 'Updated',
                'name' => $user->name,
                'email' => $user->email,
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Updated', $user->first_name);
        $this->assertTrue(Hash::check('OriginalPassword123!', $user->password));
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
