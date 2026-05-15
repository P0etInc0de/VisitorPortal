<?php

namespace AdminPanel;

use App\Enums\GenderEnum;
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
                'title' => 'Dr.',
                'gender' => GenderEnum::Male,
                'email' => 'max@example.com',
                'phone' => '+49123456789',
                'company' => 'Test GmbH',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Max',
            'name' => 'Mustermann',
            'title' => 'Dr.',
            'gender' => GenderEnum::Male,
            'email' => 'max@example.com',
            'phone' => '+49123456789',
            'company' => 'Test GmbH',
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
                'title' => 'Dr.',
                'gender' => GenderEnum::Male,
                'email' => 'max@example.com',
                'phone' => '+49123456789',
                'company' => 'Test GmbH',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Max',
            'name' => 'Mustermann',
            'title' => 'Dr.',
            'gender' => GenderEnum::Male,
            'email' => 'max@example.com',
            'phone' => '+49123456789',
            'company' => 'Test GmbH',
            'notes' => 'Test Notiz',
        ]);

        $visitor = Visitor::where('phone', '+49123456789')->first();

        Livewire::actingAs($admin)
            ->test(EditVisitor::class, ['record' => $visitor->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'first_name' => 'Erika',
                'name' => 'Mustermann',
                'title' => 'Prof.',
                'gender' => GenderEnum::Female,
                'email' => 'erika@example.com',
                'phone' => '+49987654321',
                'company' => 'Neue Firma GmbH',
                'notes' => 'Aktualisierte Notiz',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Erika',
            'name' => 'Mustermann',
            'title' => 'Prof.',
            'gender' => GenderEnum::Female,
            'email' => 'erika@example.com',
            'phone' => '+49987654321',
            'company' => 'Neue Firma GmbH',
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
                'title' => 'Dr.',
                'gender' => GenderEnum::Male,
                'email' => 'max@example.com',
                'phone' => '+49123456789',
                'company' => 'Test GmbH',
                'notes' => 'Test Notiz',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Max',
            'name' => 'Mustermann',
            'title' => 'Dr.',
            'gender' => GenderEnum::Male,
            'email' => 'max@example.com',
            'phone' => '+49123456789',
            'company' => 'Test GmbH',
            'notes' => 'Test Notiz',
        ]);

        $visitor = Visitor::where('phone', '+49123456789')->first();

        Livewire::actingAs($admin)
            ->test(EditVisitor::class, ['record' => $visitor->getRouteKey()])
            ->callAction('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('visitors', [
            'first_name' => 'Erika',
            'name' => 'Mustermann',
            'title' => 'Prof.',
            'gender' => GenderEnum::Female,
            'email' => 'erika@example.com',
            'phone' => '+49987654321',
            'company' => 'Neue Firma GmbH',
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Max',
            'name' => 'Mustermann',
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
                'first_name' => 'Max',
                'name' => 'Mustermann',
                'email' => 'max@example.com',
                'phone' => 'invalid-phone',
            ])
            ->call('create')
            ->assertHasFormErrors(['phone']);
    }
}
