<?php

// adjust to match actual permissions!

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
		// Users
		Permission::create(['name' => 'create user']);
        Permission::create(['name' => 'view all users']);
        Permission::create(['name' => 'view department users']);
		Permission::create(['name' => 'view user']);
		Permission::create(['name' => 'edit all users']);
		Permission::create(['name' => 'edit department users']);
		Permission::create(['name' => 'edit user']);
		Permission::create(['name' => 'delete all users']);
		Permission::create(['name' => 'delete department users']);
		Permission::create(['name' => 'deactivate all users']);
		Permission::create(['name' => 'deactivate department users']);
        
		//Visits
		Permission::create(['name' => 'create visit']);
		Permission::create(['name' => 'view all visits']);
		Permission::create(['name' => 'view department visits']);
		Permission::create(['name' => 'view own visits']);
		Permission::create(['name' => 'edit all visits']);
		Permission::create(['name' => 'edit department visits']);
		Permission::create(['name' => 'edit own visits']);
		Permission::create(['name' => 'delete all visits']);
		Permission::create(['name' => 'delete department visits']);
		Permission::create(['name' => 'delete own visits']);
		Permission::create(['name' => 'check in']);
		Permission::create(['name' => 'check out']);
		Permission::create(['name' => 'print']);        

		//Visitors
		Permission::create(['name' => 'create visitor']);
		Permission::create(['name' => 'view all visitors']);
		Permission::create(['name' => 'edit all visitors']);
		Permission::create(['name' => 'delete all visitors']);
        
		//Welcome Monitor
		Permission::create(['name' => 'view welcome monitor']);
		Permission::create(['name' => 'edit welcome monitor']);

		$userPermissions = [
			'view user', 'edit user', 'create visit', 'create visitor',
			'view own visits', 'edit own visits', 'delete own visits',
			'view all visitors'];

		$managerPermissions = array_merge($userPermissions, [
			'view department users', 'edit department users',
			'view department visits', 'edit department visits',
			'delete department visits']);

		$receptionistPermissions = array_merge($userPermissions, [
			'view all visits', 'edit all visits', 'delete all visits', 
			'view all visitors', 'edit all visitors',
			'check in', 'check out', 'print',
			'view welcome monitor', 'edit welcome monitor']);

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all()); // all permissions

		$receptionist = Role::create(['name' => 'receptionist']);
		$receptionist->givePermissionTo($receptionistPermissions);

        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo($managerPermissions);

        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo($userPermissions);

		$welcomemonitor = Role::create(['name' => 'welcome monitor']);
		$welcomemonitor->givePermissionTo('view welcome monitor');


        // Assign roles to users
		User::where('email', 'admin@example.com')->first()->assignRole('admin');
		User::where('email', 'manager@example.com')->first()->assignRole('manager');
		User::where('email', 'receptionist@example.com')->first()->assignRole('receptionist');
		User::where('email', 'user@example.com')->first()->assignRole('user');
		User::where('email', 'monitor@example.com')->first()->assignRole('welcome monitor');
    }
}
