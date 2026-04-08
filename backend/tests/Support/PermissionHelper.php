<?php


namespace Tests\Support;
use App\Models\User;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use Illuminate\Contracts\Container\BindingResolutionException;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PermissionHelper
{
    public function getAdminUser(): User
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
