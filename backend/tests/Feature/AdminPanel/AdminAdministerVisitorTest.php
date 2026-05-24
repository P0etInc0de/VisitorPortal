<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace AdminPanel;

use App\Enums\SalutationEnum;
use App\Filament\Resources\Visitors\Pages\CreateVisitor;
use App\Filament\Resources\Visitors\Pages\EditVisitor;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerVisitorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_admin_can_create_new_visitors(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->assertOk()
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'title' => 'Dr.',
                'salutation' => SalutationEnum::Mr,
                'email' => 'max@example.com',
                'phone' => '+493023125020',
                'company' => 'Example Industries',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Mira',
            'name' => 'Sample',
            'title' => 'Dr.',
            'salutation' => SalutationEnum::Mr,
            'email' => 'max@example.com',
            'phone' => '+493023125020',
            'company' => 'Example Industries',
            'notes' => 'Test Notiz',
        ]);
    }

    public function test_admin_can_edit_existing_visitors(): void
    {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->assertOk()
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'title' => 'Dr.',
                'salutation' => SalutationEnum::Mr,
                'email' => 'max@example.com',
                'phone' => '+493023125020',
                'company' => 'Example Industries',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Mira',
            'name' => 'Sample',
            'title' => 'Dr.',
            'salutation' => SalutationEnum::Mr,
            'email' => 'max@example.com',
            'phone' => '+493023125020',
            'company' => 'Example Industries',
            'notes' => 'Test Notiz',
        ]);

        $visitor = Visitor::where('phone', '+493023125020')->first();

        Livewire::actingAs($admin)
            ->test(EditVisitor::class, ['record' => $visitor->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'first_name' => 'Erika',
                'name' => 'Sample',
                'title' => 'Prof.',
                'salutation' => SalutationEnum::Ms,
                'email' => 'erika@example.com',
                'phone' => '+493023125021',
                'company' => 'Sample Logistics',
                'notes' => 'Aktualisierte Notiz',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Erika',
            'name' => 'Sample',
            'title' => 'Prof.',
            'salutation' => SalutationEnum::Ms,
            'email' => 'erika@example.com',
            'phone' => '+493023125021',
            'company' => 'Sample Logistics',
            'notes' => 'Aktualisierte Notiz',
        ]);

    }

    public function test_admin_can_delete_visitors(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->assertOk()
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'title' => 'Dr.',
                'salutation' => SalutationEnum::Mr,
                'email' => 'max@example.com',
                'phone' => '+493023125020',
                'company' => 'Example Industries',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Mira',
            'name' => 'Sample',
            'title' => 'Dr.',
            'salutation' => SalutationEnum::Mr,
            'email' => 'max@example.com',
            'phone' => '+493023125020',
            'company' => 'Example Industries',
            'notes' => 'Test Notiz',
        ]);

        $visitor = Visitor::where('phone', '+493023125020')->first();

        Livewire::actingAs($admin)
            ->test(EditVisitor::class, ['record' => $visitor->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('visitors', [
            'first_name' => 'Erika',
            'name' => 'Sample',
            'title' => 'Prof.',
            'salutation' => SalutationEnum::Ms,
            'email' => 'erika@example.com',
            'phone' => '+493023125021',
            'company' => 'Sample Logistics',
            'notes' => 'Aktualisierte Notiz',
        ]);
    }

    public function test_admin_cannot_create_visitor_without_required_fields(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->fillForm([])
            ->call('create')
            ->assertHasFormErrors([
                'first_name',
                'name',
            ]);
    }

    public function test_admin_cannot_create_visitor_with_invalid_email(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'email' => 'not-an-email',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_admin_can_create_visitor_without_optional_fields(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Mira',
            'name' => 'Sample',
            'email' => null,
            'phone' => null,
        ]);
    }

    public function test_admin_cannot_create_visitor_with_invalid_phone(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateVisitor::class)
            ->fillForm([
                'first_name' => 'Mira',
                'name' => 'Sample',
                'email' => 'max@example.com',
                'phone' => 'invalid-phone',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone']);
    }
}
