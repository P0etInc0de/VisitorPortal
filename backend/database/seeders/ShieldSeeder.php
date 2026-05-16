<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Shield demo permissions are disabled in production.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->roles() as $roleName => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($permissions);
        }

        $this->command?->info('Shield seeding completed.');
    }

    /**
     * @return array<string, list<string>>
     */
    private function roles(): array
    {
        $userPermissions = [
            'ViewOwn:User',
            'EditOwn:User',
            'Create:Visit',
            'Create:Visitor',
            'ViewKnown:Visitor',
            'ViewOwn:Visit',
            'EditOwn:Visit',
            'DeleteOwn:Visit',
            'View:Role',
        ];

        $managerPermissions = array_values(array_unique(array_merge($userPermissions, [
            'ViewDepartment:User',
            'EditDepartment:User',
            'ViewDepartment:Visit',
            'EditDepartment:Visit',
            'DeleteDepartment:Visit',
            'CreateForDepartment:Visit',
            'ViewDepartment:Visitor',
        ])));

        $receptionistPermissions = array_values(array_unique(array_merge($userPermissions, [
            'ViewSite:Visit',
            'EditSite:Visit',
            'DeleteSite:Visit',
            'CreateForSite:Visit',
            'ViewSite:Visitor',
            'ViewContactDetails:Visitor',
            'UpdateKnown:Visitor',
            'CheckIn:Visitor',
            'CheckOut:Visitor',
            'Print:Visitor',
            'View:Monitor',
            'ViewSite:Monitor',
            'Edit:Monitor',
            'ManageSite:Monitor',
            'Create:Monitor',
            'ViewAny:MonitorSlide',
            'View:MonitorSlide',
            'Create:MonitorSlide',
            'Update:MonitorSlide',
            'Delete:MonitorSlide',
            'Delete:Monitor',
        ])));

        $adminPermissions = array_values(array_unique(array_merge($receptionistPermissions, [
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'DeleteAny:User',
            'ManageAny:User',
            'ViewDepartment:User',
            'EditDepartment:User',
            'DeleteDepartment:User',
            'DeactivateAny:User',
            'DeactivateDepartment:User',
            'EditAny:User',
            'ViewAny:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
            'ViewAny:Department',
            'View:Department',
            'Create:Department',
            'Update:Department',
            'Delete:Department',
            'ViewAny:Site',
            'Create:Site',
            'Update:Site',
            'Delete:Site',
            'ManageAny:Site',
            'View:Visitor',
            'Update:Visitor',
            'UpdateAny:Visitor',
            'Delete:Visitor',
            'DeleteAny:Visitor',
            'ViewAny:Visitor',
            'EditAny:Visitor',
            'View:Visit',
            'Update:Visit',
            'Delete:Visit',
            'ViewAny:Visit',
            'EditAny:Visit',
            'DeleteAny:Visit',
            'CreateForAny:Visit',
            'ViewArchive:Visit',
            'ManageAny:Monitor',
            'Restore:User',
            'ForceDelete:User',
            'ForceDeleteAny:User',
            'RestoreAny:User',
            'Replicate:User',
            'Reorder:User',
            'Restore:Role',
            'ForceDelete:Role',
            'ForceDeleteAny:Role',
            'RestoreAny:Role',
            'Replicate:Role',
            'Reorder:Role',
            'Restore:Department',
            'ForceDelete:Department',
            'ForceDeleteAny:Department',
            'RestoreAny:Department',
            'Replicate:Department',
            'Reorder:Department',
            'Restore:Visitor',
            'ForceDelete:Visitor',
            'ForceDeleteAny:Visitor',
            'RestoreAny:Visitor',
            'Replicate:Visitor',
            'Reorder:Visitor',
            'Restore:Visit',
            'ForceDelete:Visit',
            'ForceDeleteAny:Visit',
            'RestoreAny:Visit',
            'Replicate:Visit',
            'Reorder:Visit',
            'View:Dashboard',
            'Update:Monitor',
            'ForceDelete:MonitorSlide',
            'ForceDeleteAny:MonitorSlide',
            'Reorder:MonitorSlide',
            'Replicate:MonitorSlide',
            'Restore:MonitorSlide',
            'RestoreAny:MonitorSlide',
            'View:UserPermissions',
        ])));

        return [
            'admin' => $adminPermissions,
            'user' => $userPermissions,
            'manager' => $managerPermissions,
            'receptionist' => $receptionistPermissions,
            'welcome monitor' => [
                'View:Monitor',
                'ViewSite:Monitor',
                'View:MonitorSlide',
                'ViewAny:MonitorSlide',
            ],
        ];
    }
}
