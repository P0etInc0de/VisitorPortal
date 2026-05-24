<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace AdminPanel;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerDepartmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Feature test for admin administering departments (CRUD))
     */
    public function test_admin_can_create_new_departments(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true,
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

    public function test_admin_can_edit_existing_departments(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true,
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
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // check if the department is updated in database
        $this->assertDatabaseHas('departments', [
            'name' => 'HR Department',
            'location' => 'Stuttgart',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_existing_departments(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        // send form to create a new department
        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->fillForm([
                'name' => 'IT Department',
                'location' => 'Berlin',
                'is_active' => true,
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

    public function test_admin_cannot_create_department_without_required_fields(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->assertOk()
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'name',
            ]);
    }

    public function test_admin_can_create_inactive_department(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateDepartment::class)
            ->assertOk()
            ->fillForm([
                'name' => 'Support Department',
                'location' => 'Munich',
                'is_active' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'name' => 'Support Department',
            'location' => 'Munich',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_department_active_state(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $department = Department::create([
            'name' => 'Finance Department',
            'location' => 'Berlin',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(EditDepartment::class, ['record' => $department->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => false,
        ]);
    }
}
