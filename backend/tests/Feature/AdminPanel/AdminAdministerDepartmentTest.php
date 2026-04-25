<?php

namespace AdminPanel;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Models\Department;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerDepartmentTest extends TestCase
{
    /**
     * Feature test for admin administering departments (CRUD))
     */
    public function test_admin_can_create_new_departments(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // check if new department is created in database
        $this->assertDatabaseHas('departments', [
            'name' => 'IT Department',
            'location' => 'Berlin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_existing_departments(): void {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // check if department has been created
        $this->assertDatabaseHas('departments', [
            'name' => 'IT Department',
            'location' => 'Berlin',
            'is_active' => true,
        ]);

        // get department for route key
        $department = Department::where('name', 'IT Department')->first();

        // send form to update database
        Livewire::actingAs($admin)
            ->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->fillForm([
                'name' => 'HR Department',
                'location' => 'Stuttgart',
                'is_active' => false
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // check if the department is updated in database
        $this->assertDatabaseHas('departments', [
            'name' => 'HR Department',
            'location' => 'Stuttgart',
            'is_active' => false
        ]);
    }

    public function test_admin_can_delete_existing_departments(): void {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // check if department has been created
        $this->assertDatabaseHas('departments', [
            'name' => 'IT Department',
            'location' => 'Berlin',
            'is_active' => true,
        ]);

        // get department for route key
        $department = Department::where('name', 'IT Department')->first();

        // send form to update database
        Livewire::actingAs($admin)
            ->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoFormErrors();

        // check if the department is updated in database
        $this->assertDatabaseMissing('departments', [
            'name' => 'IT Department',
            'location' => 'Berlin',
            'is_active' => true,
        ]);
    }
}
