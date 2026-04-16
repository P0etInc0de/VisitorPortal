<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Always reset cached roles and permissions first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $userPermissions = [
            'ViewOwn:User', 'EditOwn:User', 'Create:Visit', 'Create:Visitor',
            'ViewOwn:Visit', 'EditOwn:Visit', 'DeleteOwn:Visit',
            'ViewAny:Visitor', 'View:Role'];

        $managerPermissions = array_merge($userPermissions, [
            'ViewDepartment:User', 'EditDepartment:User',
            'ViewDepartment:Visit', 'EditDepartment:Visit',
            'DeleteDepartment:Visit']);

        $receptionistPermissions = array_merge($userPermissions, [
            'ViewAny:Visit', 'EditAny:Visit', 'DeleteAny:Visit',
            'EditAny:Visitor',
            'CheckIn:Visit', 'CheckOut:Visit', 'Print:Visit',
            'View:Monitor', 'ViewAny:Monitor', 'Edit:Monitor']);

        $roles = [
            'user' => $userPermissions,
            'manager' => $managerPermissions,
            'receptionist' => $receptionistPermissions,
            'welcome monitor' => [
                'View:Monitor', 'ViewAny:Monitor',
            ],
            'admin' => Permission::all()->pluck('name')->toArray(),
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
