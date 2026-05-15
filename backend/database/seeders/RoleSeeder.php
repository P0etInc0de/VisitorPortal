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
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo roles are disabled in production.');
        }

        // Always reset cached roles and permissions first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $userPermissions = [
            'ViewOwn:User', 'EditOwn:User', 'Create:Visit', 'Create:Visitor',
            'ViewOwn:Visit', 'EditOwn:Visit', 'DeleteOwn:Visit',
            'View:Role'];

        $managerPermissions = array_merge($userPermissions, [
            'ViewDepartment:User', 'EditDepartment:User',
            'ViewDepartment:Visit', 'EditDepartment:Visit',
            'DeleteDepartment:Visit']);

        $receptionistPermissions = array_merge($userPermissions, [
            'ViewAny:Visit', 'EditAny:Visit', 'DeleteAny:Visit',
            'EditAny:Visitor',
            'ViewAny:Visitor',
            'CheckIn:Visitor', 'CheckOut:Visitor', 'Print:Visitor',
            'View:Monitor', 'ViewAny:Monitor', 'Edit:Monitor', 'Create:Monitor',
            'ViewAny:MonitorSlide', 'View:MonitorSlide', 'Create:MonitorSlide',
            'Update:MonitorSlide', 'Delete:MonitorSlide', 'Delete:Monitor']);

        $roles = [
            'user' => $userPermissions,
            'manager' => $managerPermissions,
            'receptionist' => $receptionistPermissions,
            'welcome monitor' => [
                'View:Monitor', 'ViewAny:Monitor', 'View:MonitorSlide', 'ViewAny:MonitorSlide',
            ],
            // 'admin' => Permission::all()->pluck('name')->toArray(),
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
