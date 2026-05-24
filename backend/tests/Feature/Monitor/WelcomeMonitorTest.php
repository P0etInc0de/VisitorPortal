<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Monitor;

use App\Enums\VisitStatusEnum;
use App\Models\Monitor;
use App\Models\Site;
use App\Models\Visit;
use App\Models\Visitor;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class WelcomeMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_settings_can_be_saved_from_the_livewire_editor(): void
    {
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
            ->set('monitorDisplayMode', Monitor::DISPLAY_TITLE_FULL_NAME)
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
        $this->assertSame(Monitor::DISPLAY_TITLE_FULL_NAME, $monitor->monitor_display_mode);

        $this->assertSame(0, $monitor->monitorSlides()->count());
    }

    public function test_monitor_settings_livewire_component_requires_update_permission(): void
    {
        $user = (new PermissionHelper)->getIndividualUser(['View:Monitor'], 'monitor-viewer');
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        Livewire::actingAs($user)
            ->test('monitor.settings-form', ['monitor' => $monitor])
            ->assertForbidden();
    }

    public function test_monitor_defaults_to_fallback_only_with_title_first_initial_last_name_mode(): void
    {
        $monitor = Monitor::query()->create([
            'name' => 'Reception',
            'transition_time_milliseconds' => 5000,
        ]);

        $this->assertFalse($monitor->auto_generation);
        $this->assertSame(Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME, $monitor->monitor_display_mode);
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
                'heading' => 'Welcome to VisitorPortal',
                'subheading' => 'Bitte melden Sie sich am Empfang.',
                'show_logo' => '0',
                'show_date' => '1',
                'background_source' => 'preset-2',
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $monitor->refresh();

        $this->assertSame('Welcome to VisitorPortal', $monitor->fallback_heading);
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
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
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
        $this->assertSame(Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL, $slide->monitor_display_mode);
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
        $visitor = Visitor::factory()->create([
            'first_name' => 'Theo',
            'name' => 'Pünktlich',
        ]);
        $visit = Visit::factory()->create([
            'site_id' => $monitor->site_id,
            'is_confidential' => false,
        ]);
        $visit->visitors()->attach($visitor->id);

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
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
                'visitors' => json_encode([
                    ['id' => $visitor->id, 'name' => 'Theo Pünktlich'],
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
        $this->assertSame(Monitor::DISPLAY_TITLE_FULL_NAME, $slide->monitor_display_mode);
        $this->assertSame([
            ['id' => $visitor->id, 'name' => 'Theo Pünktlich'],
            ['id' => null, 'name' => 'Gast ohne Termin'],
        ], $slide->visitors);
    }

    public function test_monitor_slide_rejects_numeric_visitor_from_another_site(): void
    {
        $user = $this->monitorUser();
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $monitor = Monitor::query()->create([
            'site_id' => $site->id,
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $user->forceFill(['site_id' => $site->id])->save();
        $visitor = Visitor::factory()->create(['first_name' => 'Foreign', 'name' => 'Guest']);
        $visit = Visit::factory()->create([
            'site_id' => $otherSite->id,
            'is_confidential' => false,
        ]);
        $visit->visitors()->attach($visitor->id);

        $response = $this
            ->actingAs($user)
            ->from(route('monitors.slides.create', $monitor))
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
                'visitors' => json_encode([
                    ['id' => $visitor->id, 'name' => 'Foreign Guest'],
                ]),
            ]);

        $response
            ->assertRedirect(route('monitors.slides.create', $monitor))
            ->assertSessionHasErrors('visitors');

        $this->assertSame(0, $monitor->monitorSlides()->count());
    }

    public function test_monitor_slide_rejects_numeric_visitor_from_confidential_visit(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $visitor = Visitor::factory()->create(['first_name' => 'Secret', 'name' => 'Guest']);
        $visit = Visit::factory()->create([
            'site_id' => $monitor->site_id,
            'is_confidential' => true,
        ]);
        $visit->visitors()->attach($visitor->id);

        $response = $this
            ->actingAs($user)
            ->from(route('monitors.slides.create', $monitor))
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
                'visitors' => json_encode([
                    ['id' => $visitor->id, 'name' => 'Secret Guest'],
                ]),
            ]);

        $response
            ->assertRedirect(route('monitors.slides.create', $monitor))
            ->assertSessionHasErrors('visitors');

        $this->assertSame(0, $monitor->monitorSlides()->count());
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
            ->withSession(['locale' => 'de'])
            ->actingAs($user)
            ->get(route('monitors.slides.create', $monitor))
            ->assertOk()
            ->assertSee(__('Seite aktiv'))
            ->assertSee(__('Besucheranzeige'))
            ->assertSee(__('Manuell Name hinzufügen'))
            ->assertSee('value="We&#039;re glad you&#039;re here."', false)
            ->assertDontSee('We&amp;#039;re', false);

        $this
            ->actingAs($user)
            ->get(route('monitors.slides.edit', [$monitor, $slide]))
            ->assertOk()
            ->assertSee(__('Seite aktiv'))
            ->assertSee(__('Besucheranzeige'))
            ->assertSee(__('Manuell Name hinzufügen'));
    }

    public function test_monitor_slide_editor_shows_compact_selection_counter_and_feedback_hooks(): void
    {
        app()->setLocale('de');

        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'Chip',
        ]);
        $visit = Visit::factory()->create([
            'site_id' => $monitor->site_id,
            'is_confidential' => false,
        ]);

        $visit->visitors()->attach($visitor->id);

        $this
            ->withSession(['locale' => 'de'])
            ->actingAs($user)
            ->get(route('monitors.slides.create', $monitor))
            ->assertOk()
            ->assertSee('id="selectedVisitors" class="flex max-h-64 min-h-14', false)
            ->assertSee('id="visitorCount"', false)
            ->assertSee('0 von 6 Besuchern ausgewählt')
            ->assertSee('Maximal 6 Besucher je Seite')
            ->assertSee('Besucher ist bereits hinzugefügt.')
            ->assertSee('Maximal 6 Besucher je Seite erreicht.')
            ->assertSee('function addVisitor(visitor)', false)
            ->assertSee('function addManualVisitor()', false)
            ->assertSee('const wasAdded = addVisitor({', false)
            ->assertSee('onclick="addVisitor(', false)
            ->assertSee('showVisitorFeedback(duplicateVisitorMessage)', false)
            ->assertSee('showVisitorFeedback(visitorLimitReachedMessage)', false)
            ->assertDontSee('xl:items-stretch', false)
            ->assertDontSee('flex h-full min-w-0 flex-col rounded-2xl', false)
            ->assertDontSee('flex min-h-14 flex-1 flex-wrap', false);
    }

    public function test_monitor_slide_store_deduplicates_duplicate_selected_visitors(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Theo',
            'name' => 'Pünktlich',
            'title' => '',
        ]);
        $visit = Visit::factory()->create([
            'site_id' => $monitor->site_id,
            'is_confidential' => false,
        ]);

        $visit->visitors()->attach($visitor->id);

        $response = $this
            ->actingAs($user)
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
                'visitors' => json_encode([
                    ['id' => $visitor->id, 'name' => 'Theo Pünktlich'],
                    ['id' => $visitor->id, 'name' => 'Theo Pünktlich'],
                    ['id' => null, 'name' => 'Theo Pünktlich'],
                ]),
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $slide = $monitor->monitorSlides()->sole();

        $this->assertSame([
            ['id' => $visitor->id, 'name' => 'Theo Pünktlich'],
        ], $slide->visitors);
    }

    public function test_monitor_slide_store_accepts_six_visitors(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $visitors = collect(range(1, 6))
            ->map(fn (int $index): array => ['id' => null, 'name' => 'Gast '.$index])
            ->all();

        $response = $this
            ->actingAs($user)
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
                'visitors' => json_encode($visitors),
            ]);

        $response->assertRedirect(route('monitors.edit', $monitor).'#monitor-pages');

        $this->assertCount(6, $monitor->monitorSlides()->sole()->visitors);
    }

    public function test_monitor_slide_store_rejects_more_than_six_visitors(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);
        $visitors = collect(range(1, 7))
            ->map(fn (int $index): array => ['id' => null, 'name' => 'Gast '.$index])
            ->all();

        $response = $this
            ->actingAs($user)
            ->from(route('monitors.slides.create', $monitor))
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
                'visitors' => json_encode($visitors),
            ]);

        $response
            ->assertRedirect(route('monitors.slides.create', $monitor))
            ->assertSessionHasErrors('visitors');

        $this->assertSame(0, $monitor->monitorSlides()->count());
    }

    public function test_monitor_slide_rejects_manual_names_longer_than_50_characters(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('monitors.slides.create', $monitor))
            ->post(route('monitors.slides.store', $monitor), [
                'heading' => 'Herzlich willkommen!',
                'subheading' => 'Schön, dass Sie da sind!',
                'slide_number' => 1,
                'is_active' => '1',
                'show_logo' => '1',
                'show_date' => '1',
                'background_source' => 'inherit',
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
                'visitors' => json_encode([
                    ['id' => null, 'name' => str_repeat('A', 51)],
                ]),
            ]);

        $response
            ->assertRedirect(route('monitors.slides.create', $monitor))
            ->assertSessionHasErrors('visitors');

        $this->assertSame(0, $monitor->monitorSlides()->count());
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

    public function test_monitor_settings_show_validation_errors_for_invalid_numeric_input(): void
    {
        $user = $this->monitorUser();
        $monitor = Monitor::query()->create([
            'name' => 'Empfang',
            'transition_time_milliseconds' => 5000,
            'auto_generation' => false,
        ]);

        $this->actingAs($user);

        Livewire::test('monitor.settings-form', ['monitor' => $monitor])
            ->set('autoGenerationWindowMinutes', '18ß')
            ->assertHasErrors(['autoGenerationWindowMinutes'])
            ->set('transitionTimeSeconds', '')
            ->assertHasErrors(['transitionTimeSeconds']);
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
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
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

            $this->assertSame(config('branding.monitor_slide_heading', 'Welcome!'), $slide->heading);
            $this->assertTrue($slide->is_auto_generated);
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

    public function test_auto_generation_includes_overlapping_planned_visits_and_ignores_drafts(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $monitor = Monitor::query()->create([
                'name' => 'Empfang',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
            ]);

            $overlappingPlannedVisit = Visit::factory()->create([
                'scheduled_from' => now()->subMinutes(45),
                'scheduled_until' => now()->addMinutes(15),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $draftVisit = Visit::factory()->create([
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(35),
                'status' => VisitStatusEnum::Draft->value,
            ]);

            $plannedVisitor = Visitor::factory()->create([
                'title' => '',
                'first_name' => 'Clara',
                'name' => 'Geplant',
            ]);

            $draftVisitor = Visitor::factory()->create([
                'title' => '',
                'first_name' => 'Dario',
                'name' => 'Entwurf',
            ]);

            $overlappingPlannedVisit->visitors()->attach($plannedVisitor);
            $draftVisit->visitors()->attach($draftVisitor);

            (new WelcomeMonitorAutoGeneration)();

            $slide = $monitor->monitorSlides()->sole();

            $this->assertTrue($slide->is_auto_generated);
            $this->assertSame([
                [
                    'id' => $plannedVisitor->id,
                    'name' => 'Clara Geplant',
                ],
            ], $slide->visitors);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_auto_generation_uses_monitor_display_mode_and_deduplicates_company_names(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $monitor = Monitor::query()->create([
                'name' => 'Empfang',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_COMPANY_ONLY,
            ]);

            $visit = Visit::factory()->create([
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $firstAcmeVisitor = Visitor::factory()->create(['company' => 'Acme GmbH']);
            $secondAcmeVisitor = Visitor::factory()->create(['company' => 'Acme GmbH']);
            $otherVisitor = Visitor::factory()->create(['company' => 'Other GmbH']);

            $visit->visitors()->attach([$firstAcmeVisitor->id, $secondAcmeVisitor->id, $otherVisitor->id]);

            (new WelcomeMonitorAutoGeneration)();

            $slide = $monitor->monitorSlides()->sole();

            $this->assertSame(Monitor::DISPLAY_COMPANY_ONLY, $slide->monitor_display_mode);
            $this->assertSame([
                [
                    'id' => null,
                    'name' => 'Acme GmbH',
                ],
                [
                    'id' => null,
                    'name' => 'Other GmbH',
                ],
            ], $slide->visitors);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_auto_generation_keeps_manual_pages_and_only_replaces_generated_pages(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $monitor = Monitor::query()->create([
                'name' => 'Empfang',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
            ]);

            $manualSlide = $monitor->monitorSlides()->create([
                'heading' => 'Manuell',
                'subheading' => 'Soll bleiben',
                'slide_number' => 1,
                'is_active' => true,
                'is_auto_generated' => false,
                'show_logo' => true,
                'show_date' => true,
                'visitors' => [
                    ['id' => null, 'name' => 'Theo Pünktlich'],
                ],
            ]);

            $oldGeneratedSlide = $monitor->monitorSlides()->create([
                'heading' => 'Alt generiert',
                'subheading' => 'Wird ersetzt',
                'slide_number' => 1,
                'is_active' => true,
                'is_auto_generated' => true,
                'show_logo' => true,
                'show_date' => true,
                'visitors' => [],
            ]);

            $visit = Visit::factory()->create([
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $visitor = Visitor::factory()->create([
                'title' => '',
                'first_name' => 'Anna',
                'name' => 'Beispiel',
            ]);

            $visit->visitors()->attach($visitor);

            (new WelcomeMonitorAutoGeneration)();

            $this->assertDatabaseHas('monitor_slides', [
                'id' => $manualSlide->id,
                'is_auto_generated' => false,
            ]);

            $this->assertDatabaseMissing('monitor_slides', [
                'id' => $oldGeneratedSlide->id,
            ]);

            $generatedSlides = $monitor->monitorSlides()
                ->where('is_auto_generated', true)
                ->get();

            $this->assertCount(1, $generatedSlides);
            $this->assertSame([
                [
                    'id' => $visitor->id,
                    'name' => 'Anna Beispiel',
                ],
            ], $generatedSlides->sole()->visitors);
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
            'fallback_heading' => 'Welcome to VisitorPortal',
            'fallback_subheading' => 'Bitte wenden Sie sich an den Empfang.',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('monitors.show', $monitor));

        $response
            ->assertOk()
            ->assertSee('Welcome to VisitorPortal')
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

    public function test_live_display_ignores_manual_pages_while_auto_generation_is_active(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $user = $this->monitorUser();
            $monitor = Monitor::query()->create([
                'name' => 'Empfang',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'fallback_heading' => 'Fallback aktiv',
                'fallback_subheading' => 'Keine Auto-Seite vorhanden.',
            ]);

            $monitor->monitorSlides()->create([
                'heading' => 'Manuelle Seite',
                'subheading' => 'Soll nicht angezeigt werden',
                'slide_number' => 1,
                'is_active' => true,
                'is_auto_generated' => false,
                'show_logo' => true,
                'show_date' => true,
                'visitors' => [
                    ['id' => null, 'name' => 'Manuell'],
                ],
            ]);

            $response = $this
                ->actingAs($user)
                ->get(route('monitors.show', $monitor));

            $response
                ->assertOk()
                ->assertSee('Fallback aktiv')
                ->assertDontSee('Manuelle Seite')
                ->assertDontSee('Manuell');
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_auto_generation_is_site_scoped_and_hides_confidential_visits(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $site = Site::factory()->create(['name' => 'Standort Ulm']);
            $otherSite = Site::factory()->create(['name' => 'Standort Neu-Ulm']);
            $monitor = Monitor::query()->create([
                'site_id' => $site->id,
                'name' => 'Empfang Ulm',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_COMPANY_ONLY,
            ]);

            $visibleVisit = Visit::factory()->create([
                'site_id' => $site->id,
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $otherSiteVisit = Visit::factory()->create([
                'site_id' => $otherSite->id,
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $confidentialVisit = Visit::factory()->create([
                'site_id' => $site->id,
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
                'is_confidential' => true,
            ]);

            $visibleVisitor = Visitor::factory()->create(['company' => 'Acme GmbH']);
            $otherSiteVisitor = Visitor::factory()->create(['company' => 'Other GmbH']);
            $confidentialVisitor = Visitor::factory()->create(['first_name' => 'Secret', 'name' => 'Guest', 'company' => 'Secret GmbH']);

            $visibleVisit->visitors()->attach($visibleVisitor);
            $otherSiteVisit->visitors()->attach($otherSiteVisitor);
            $confidentialVisit->visitors()->attach($confidentialVisitor);

            (new WelcomeMonitorAutoGeneration)($monitor);

            $slide = $monitor->monitorSlides()->sole();

            $this->assertSame([
                [
                    'id' => null,
                    'name' => 'Acme GmbH',
                ],
            ], $slide->visitors);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_auto_generation_skips_monitors_for_inactive_sites(): void
    {
        Carbon::setTestNow('2026-04-24 10:00:00');

        try {
            $inactiveSite = Site::factory()->create(['is_active' => false]);
            $monitor = Monitor::query()->create([
                'site_id' => $inactiveSite->id,
                'name' => 'Inactive Site Monitor',
                'transition_time_milliseconds' => 5000,
                'auto_generation' => true,
                'auto_generation_window_minutes' => 30,
                'monitor_display_mode' => Monitor::DISPLAY_TITLE_FULL_NAME,
            ]);
            $visit = Visit::factory()->create([
                'site_id' => $inactiveSite->id,
                'scheduled_from' => now()->addMinutes(5),
                'scheduled_until' => now()->addMinutes(65),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $visitor = Visitor::factory()->create(['first_name' => 'Inactive', 'name' => 'Guest']);

            $visit->visitors()->attach($visitor->id);

            (new WelcomeMonitorAutoGeneration)($monitor);

            $this->assertSame(0, $monitor->monitorSlides()->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function monitorUser()
    {
        return (new PermissionHelper)->getIndividualUser([
            'View:Monitor',
            'Edit:Monitor',
            'Create:MonitorSlide',
            'Update:MonitorSlide',
            'Delete:MonitorSlide',
        ], 'monitor-user');
    }
}
