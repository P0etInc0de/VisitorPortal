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
    private array $permissions;

    public function __construct()
    {
        $this->permissions = [
            // User
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'Restore:User',
            'ForceDelete:User',
            'ForceDeleteAny:User',
            'RestoreAny:User',
            'Replicate:User',
            'Reorder:User',

            // Role
            'ViewAny:Role',
            'View:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
            'Restore:Role',
            'ForceDelete:Role',
            'ForceDeleteAny:Role',
            'RestoreAny:Role',
            'Replicate:Role',
            'Reorder:Role',

            // Department
            'ViewAny:Department',
            'View:Department',
            'Create:Department',
            'Update:Department',
            'Delete:Department',
            'Restore:Department',
            'ForceDelete:Department',
            'ForceDeleteAny:Department',
            'RestoreAny:Department',
            'Replicate:Department',
            'Reorder:Department',

            // Visitor
            'ViewAny:Visitor',
            'View:Visitor',
            'Create:Visitor',
            'Update:Visitor',
            'Delete:Visitor',
            'Restore:Visitor',
            'ForceDelete:Visitor',
            'ForceDeleteAny:Visitor',
            'RestoreAny:Visitor',
            'Replicate:Visitor',
            'Reorder:Visitor',

            // Visit
            'ViewAny:Visit',
            'View:Visit',
            'Create:Visit',
            'Update:Visit',
            'Delete:Visit',
            'Restore:Visit',
            'ForceDelete:Visit',
            'ForceDeleteAny:Visit',
            'RestoreAny:Visit',
            'Replicate:Visit',
            'Reorder:Visit',

            // Dashboard
            'View:Dashboard',
        ];
    }

    public function getSuperAdminUser(): User
    {
        return $this->getIndividualUser($this->permissions, "super_admin");
    }

    public function getIndividualUser(array $individualPermissions, string $name):User
    {
        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

        foreach ($individualPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        $role->syncPermissions($individualPermissions);
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

}
