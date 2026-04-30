<?php

namespace Tests\Feature\Monitor;

use App\Enums\VisitStatusEnum;
use App\Models\Monitor;
use App\Models\Visit;
use App\Models\Visitor;
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
            ->set('headerTextIsLight', true)
            ->set('contentCardStyle', 'none')
            ->call('save')
            ->assertHasNoErrors();

        $monitor->refresh();

        $this->assertSame(8000, $monitor->transition_time_milliseconds);
        $this->assertTrue($monitor->auto_generation);
        $this->assertSame(45, $monitor->auto_generation_window_minutes);
        $this->assertSame('preset-3', $monitor->background_source);
        $this->assertTrue($monitor->background_overlay_enabled);
        $this->assertTrue($monitor->header_text_is_light);
        $this->assertSame('none', $monitor->content_card_style);

        Queue::assertPushed(WelcomeMonitorAutoGeneration::class);
    }

    public function test_fallback_page_can_be_updated_from_its_dedicated_edit_flow(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('monitors.fallback.update', $monitor), [
                'heading' => 'Willkommen bei ACD',
                'subheading' => 'Bitte melden Sie sich am Empfang.',
                'show_logo' => '0',
                'show_date' => '1',
                'background_source' => 'preset-2',
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $monitor->refresh();

        $this->assertSame('Willkommen bei ACD', $monitor->fallback_heading);
        $this->assertSame('Bitte melden Sie sich am Empfang.', $monitor->fallback_subheading);
        $this->assertFalse($monitor->fallback_show_logo);
        $this->assertTrue($monitor->fallback_show_date);
        $this->assertSame('preset-2', $monitor->fallback_background_source);
    }

    public function test_monitor_slide_can_be_created_from_the_editor_flow(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '0',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'visitors' => json_encode([
                    ['id' => null, 'name' => 'Theo Pünktlich'],
                ]),
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $slide = $monitor->monitorSlides()->sole();

        $this->assertSame('Herzlich willkommen!', $slide->heading);
        $this->assertSame('Schön, dass Sie da sind!', $slide->subheading);
        $this->assertSame(1, $slide->slide_number);
        $this->assertFalse($slide->is_active);
        $this->assertTrue($slide->show_logo);
        $this->assertTrue($slide->show_date);
        $this->assertNull($slide->background_source);
        $this->assertNull($slide->image_path);
        $this->assertSame([
            ['id' => null, 'name' => 'Theo Pünktlich'],
        ], $slide->visitors);
    }

    public function test_monitor_slide_can_be_updated_from_the_editor_flow(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Alte Seite',
            'subheading' => 'Alt',
            'slide_number' => 1,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [],
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('monitors.slides.update', [$monitor, $slide]), [
                'heading' => 'Neue Seite',
                'subheading' => 'Aktualisiert',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '0',
                'show_date' => '0',
                'background_source' => 'preset-2',
                'visitors' => json_encode([
                    ['id' => 5, 'name' => 'Theo Pünktlich'],
                    ['id' => null, 'name' => 'Gast ohne Termin'],
                ]),
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $slide->refresh();

        $this->assertSame('Neue Seite', $slide->heading);
        $this->assertSame('Aktualisiert', $slide->subheading);
        $this->assertTrue($slide->is_active);
        $this->assertFalse($slide->show_logo);
        $this->assertFalse($slide->show_date);
        $this->assertSame('preset-2', $slide->background_source);
        $this->assertSame([
            ['id' => 5, 'name' => 'Theo Pünktlich'],
            ['id' => null, 'name' => 'Gast ohne Termin'],
        ], $slide->visitors);
    }

    public function test_monitor_slide_editor_pages_render_the_new_manual_name_and_visibility_controls(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Bestehende Seite',
            'subheading' => 'Schon da',
            'slide_number' => 1,
            'is_active' => true,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [],
        ]);

        $this
            ->actingAs($user)
            ->get(route('monitors.slides.create', $monitor))
            ->assertOk()
            ->assertSee(__('Seite aktiv'))
            ->assertSee(__('Eigenen Namen hinzufügen'));

        $this
            ->actingAs($user)
            ->get(route('monitors.slides.edit', [$monitor, $slide]))
            ->assertOk()
            ->assertSee(__('Seite aktiv'))
            ->assertSee(__('Eigenen Namen hinzufügen'));
    }

    public function test_manual_page_visibility_can_be_toggled_from_the_editor_component(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Sichtbar',
            'subheading' => 'Noch aktiv',
            'slide_number' => 1,
            'is_active' => true,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [],
        ]);

        $this->actingAs($user);

        Livewire::test('monitor.index-monitor-slides', ['monitor' => $monitor])
            ->call('toggleSlideVisibility', $slide->id);

        $slide->refresh();

        $this->assertFalse($slide->is_active);
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

            $this->assertSame(__('Herzlich willkommen!'), $slide->heading);
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

    public function test_live_display_renders_active_manual_pages_without_linked_visits(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
            'header_text_is_light' => true,
        ]);

        $monitor->monitorSlides()->create([
            'heading' => 'Herzlich willkommen!',
            'subheading' => 'Schön, dass Sie da sind!',
            'slide_number' => 1,
            'is_active' => true,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [
                ['id' => null, 'name' => 'Gast ohne Termin'],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Herzlich willkommen!')
            ->assertSee('Gast ohne Termin')
            ->assertSee('text-white');
    }

    public function test_live_display_falls_back_when_all_manual_pages_are_inactive(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
            'fallback_heading' => 'Fallback aktiv',
            'fallback_subheading' => 'Keine aktive Seite vorhanden.',
        ]);

        $monitor->monitorSlides()->create([
            'heading' => 'Nicht sichtbar',
            'subheading' => 'Inaktiv',
            'slide_number' => 1,
            'is_active' => false,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [
                ['id' => null, 'name' => 'Versteckt'],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Fallback aktiv')
            ->assertDontSee('Nicht sichtbar')
            ->assertDontSee('Versteckt');
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
