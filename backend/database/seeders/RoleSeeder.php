<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Always reset cached roles and permissions first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

		// Create custom permissions that Shield won't generate
   		$customPermissions = [
        	'checkIn:Visit', 'checkOut:Visit', 'print:Visit',
			'deleteAny:Visit', 'Create:Visit', 'ViewAny:Visit', 'editAny:Visit',
        	'viewOwn:Visit', 'editOwn:Visit', 'deleteOwn:Visit',
        	'viewDepartment:Visit', 'editDepartment:Visit', 'deleteDepartment:Visit',
			'deleteAny:Visitor', 'Create:Visitor', 'ViewAny:Visitor', 'editAny:Visitor', 'deleteAny:Visitor',
        	'viewAny:User', 'editAny:User', 'deleteAny:User', 'viewOwn:User', 'editOwn:User',
        	'viewDepartment:User', 'editDepartment:User', 'deleteDepartment:User',
        	'deactivateAll:User', 'deactivateDepartment:User', 'Create:User', 'Edit:Monitor', 'View:Monitor',
    	];

    	foreach ($customPermissions as $permission) {
        	Permission::firstOrCreate([
            	'name' => $permission,
            	'guard_name' => 'web',
        	]);
    	}

		$userPermissions = [ 
            'viewOwn:User', 'editOwn:User', 'Create:Visit', 'Create:Visitor',
            'viewOwn:Visit', 'editOwn:Visit', 'deleteOwn:Visit',
            'ViewAny:Visitor'];

        $managerPermissions = array_merge($userPermissions, [
            'viewDepartment:User', 'editDepartment:User',
            'viewDepartment:Visit', 'editDepartment:Visit',
            'deleteDepartment:Visit']);

        $receptionistPermissions = array_merge($userPermissions, [
            'ViewAny:Visit', 'editAny:Visit', 'deleteAny:Visit', 
            'editAny:Visitor',
            'checkIn:Visit', 'checkOut:Visit', 'print:Visit',
            'View:Monitor', 'Edit:Monitor']);

        $roles = [
			'user' => $userPermissions,
			'manager' => $managerPermissions,
            'receptionist' => $receptionistPermissions,
            'welcome monitor' => [
                'View:Monitor',
            ],
            'admin' => Permission::all()->pluck('name')->toArray(),
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
