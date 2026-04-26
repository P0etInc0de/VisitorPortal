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
            ->set('backgroundSource', 'preset-3')
            ->set('backgroundOverlayEnabled', true)
            ->set('contentCardStyle', 'none')
            ->set('fallbackHeading', 'Willkommen bei ACD')
            ->set('fallbackSubheading', 'Bitte melden Sie sich am Empfang.')
            ->set('fallbackShowLogo', false)
            ->set('fallbackShowDate', false)
            ->call('save')
            ->assertHasNoErrors();

        $monitor->refresh();

        $this->assertSame(8000, $monitor->transition_time_milliseconds);
        $this->assertTrue($monitor->auto_generation);
        $this->assertSame(45, $monitor->auto_generation_window_minutes);
        $this->assertSame('preset-3', $monitor->background_source);
        $this->assertTrue($monitor->background_overlay_enabled);
        $this->assertSame('none', $monitor->content_card_style);
        $this->assertSame('Willkommen bei ACD', $monitor->fallback_heading);
        $this->assertSame('Bitte melden Sie sich am Empfang.', $monitor->fallback_subheading);
        $this->assertFalse($monitor->fallback_show_logo);
        $this->assertFalse($monitor->fallback_show_date);

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
            'fallback_heading' => 'Willkommen im Besucherportal',
            'fallback_subheading' => 'Bitte wenden Sie sich an den Empfang.',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Willkommen im Besucherportal')
            ->assertSee('Bitte wenden Sie sich an den Empfang.');
    }

    public function test_live_display_uses_slide_specific_background_when_configured(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
            'background_source' => 'preset-1',
        ]);

        $monitor->monitorSlides()->create([
            'heading' => 'Sonderseite',
            'subheading' => 'Mit eigenem Hintergrund',
            'slide_number' => 1,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [],
            'background_source' => 'preset-3',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Sonderseite')
            ->assertSee('monitor-default-3.jpg');
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
