<?php

namespace Tests\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionHelper
{
    private array $permissions;

    public function __construct()
    {
        $this->permissions = [
            // User
            'View:User',
            'ViewAny:User',
            'ViewDepartment:User',
            'ViewOwn:User',
            'Create:User',
            'Update:User',
            'EditAny:User',
            'EditDepartment:User',
            'EditOwn:User',
            'Delete:User',
            'DeleteAny:User',
            'DeleteDepartment:User',
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
            'EditAny:Visitor',
            'Delete:Visitor',
            'DeleteAny:Visitor',
            'Restore:Visitor',
            'ForceDelete:Visitor',
            'ForceDeleteAny:Visitor',
            'RestoreAny:Visitor',
            'Replicate:Visitor',
            'Reorder:Visitor',

            // Visit
            'View:Visit',
            'ViewAny:Visit',
            'ViewDepartment:Visit',
            'ViewOwn:Visit',
            'Create:Visit',
            'Update:Visit',
            'EditAny:Visit',
            'EditDepartment:Visit',
            'EditOwn:Visit',
            'Delete:Visit',
            'DeleteAny:Visit',
            'DeleteDepartment:Visit',
            'DeleteOwn:Visit',
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
        return $this->getIndividualUser($this->permissions, 'super_admin');
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

    public function getUser(): User
    {
        $userPermissions = [
            'ViewOwn:User', 'EditOwn:User', 'Create:Visit', 'Create:Visitor',
            'ViewOwn:Visit', 'EditOwn:Visit', 'DeleteOwn:Visit',
            'ViewAny:Visitor', 'View:Role', 'DeleteAny:User'];

        return $this->getIndividualUser($userPermissions, 'user');
    }
}
