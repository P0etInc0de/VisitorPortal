<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use Illuminate\Contracts\Container\BindingResolutionException;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminAdministerUserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Feature test for testing the Admin area
     * @throws BindingResolutionException
     */
    public function test_admin_can_create_users(): void
    {

        $admin = $this->createAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertOk()
            ->fillForm([
                'name' => 'Test User',
                'email' => 'testuser@example.com',
                'password' => 'password',
                'roles' => [Role::whereName('admin')->first()->id]
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

    }

    /**
     * @throws BindingResolutionException
     */
    private function createAdminUser(): User
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();


        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $permissions = [
            'ViewAny:User',
            'Create:User',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }


        $adminRole->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($adminRole);

        return $user;
    }
}
