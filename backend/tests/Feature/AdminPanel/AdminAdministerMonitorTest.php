<?php

namespace AdminPanel;

use App\Filament\Resources\Monitors\Pages\CreateMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AdminAdministerMonitorTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */

    public function test_admin_can_create_monitors(): void {

        $admin = (new PermissionHelper)->getSuperAdminUser();

        Livewire::actingAs($admin)
            ->test(CreateMonitor::class)
            ->assertSuccessful()
            ->fillForm([
                'name' => 'Monitor',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('monitors', [
            'name' => 'Monitor',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => true,
        ]);
    }
}
