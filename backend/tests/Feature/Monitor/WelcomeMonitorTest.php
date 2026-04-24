<?php

namespace Tests\Feature\Monitor;

use App\Enums\VisitStatusEnum;
use App\Models\Monitor;
use App\Models\Visitor;
use App\Models\Visit;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class WelcomeMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_settings_can_be_saved_from_the_livewire_editor(): void
    {
        Queue::fake();

        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $this->actingAs($user);

        Livewire::test('monitor.settings-form', ['monitor' => $monitor])
            ->set('transitionTimeSeconds', 8)
            ->set('autoGeneration', true)
            ->set('autoGenerationWindowMinutes', 45)
            ->set('backgroundSource', 'preset-2')
            ->set('backgroundOverlayEnabled', true)
            ->set('contentCardStyle', 'none')
            ->call('save')
            ->assertHasNoErrors();

        $monitor->refresh();

        $this->assertSame(8000, $monitor->transition_time_milliseconds);
        $this->assertTrue($monitor->auto_generation);
        $this->assertSame(45, $monitor->auto_generation_window_minutes);
        $this->assertSame('preset-2', $monitor->background_source);
        $this->assertTrue($monitor->background_overlay_enabled);
        $this->assertSame('none', $monitor->content_card_style);

        Queue::assertPushed(WelcomeMonitorAutoGeneration::class);
    }

    public function test_auto_generation_uses_the_configured_monitor_window(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $monitor = Monitor::query()->create([
                'name' => 'Empfang',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 10,
            ]);

            $insideVisit = Visit::factory()->create([
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $outsideVisit = Visit::factory()->create([
                'scheduled_from' => now()->addMinutes(25),
                'scheduled_until' => now()->addMinutes(85),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $insideVisitor = Visitor::factory()->create([
                'title' => '',
                'first_name' => 'Anna',
                'name' => 'Beispiel',
            ]);

            $outsideVisitor = Visitor::factory()->create([
                'title' => '',
                'first_name' => 'Max',
                'name' => 'Außerhalb',
            ]);

            $insideVisit->visitors()->attach($insideVisitor);
            $outsideVisit->visitors()->attach($outsideVisitor);

            (new WelcomeMonitorAutoGeneration)();

            $slide = $monitor->monitorSlides()->sole();

            $this->assertSame('Herzlich willkommen!', $slide->heading);
            $this->assertSame([
                [
                    'id' => $insideVisitor->id,
                    'name' => 'Anna Beispiel',
                ],
            ], $slide->visitors);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_live_display_renders_a_fallback_slide_when_no_pages_exist(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Herzlich willkommen bei ACD')
            ->assertSee('Wir wünschen Ihnen einen angenehmen Aufenthalt.');
    }

    private function monitorUser()
    {
        return (new PermissionHelper)->getIndividualUser([
            'View:Monitor',
            'Update:Monitor',
            'Create:MonitorSlide',
            'Update:MonitorSlide',
            'Delete:MonitorSlide',
        ], 'monitor-user');
    }
}
