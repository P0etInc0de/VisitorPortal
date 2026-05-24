<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Security;

use App\Enums\VisitStatusEnum;
use App\Livewire\Portal\CheckInOutBoard;
use App\Models\DataRetentionRun;
use App\Models\Department;
use App\Models\Monitor;
use App\Models\RecurringVisitSeries;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_open_foreign_visit_show_page(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $owner->id,
            'substitute_user_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('portal.visits.show', $visit))
            ->assertForbidden();
    }

    public function test_department_scoped_user_without_department_cannot_view_unassigned_department_visit(): void
    {
        $manager = (new PermissionHelper)->getManagerUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $owner->id,
            'substitute_user_id' => null,
        ]);

        $this->assertNull($manager->department_id);
        $this->assertNull($visit->host?->department_id);

        $this->actingAs($manager)
            ->get(route('portal.visits.show', $visit))
            ->assertForbidden();
    }

    public function test_department_scoped_user_without_department_cannot_view_unassigned_department_user(): void
    {
        $manager = (new PermissionHelper)->getManagerUser();
        $otherUser = (new PermissionHelper)->getUserUser();

        $this->assertNull($manager->department_id);
        $this->assertNull($otherUser->department_id);
        $this->assertFalse($manager->can('view', $otherUser));
    }

    public function test_user_department_must_belong_to_user_primary_site(): void
    {
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $otherSiteDepartment = Department::query()->create([
            'site_id' => $otherSite->id,
            'name' => 'Other Site Department',
        ]);

        $this->expectException(ValidationException::class);

        User::factory()->create([
            'site_id' => $site->id,
            'department_id' => $otherSiteDepartment->id,
        ]);
    }

    public function test_visit_requires_explicit_site_id_on_create(): void
    {
        $host = (new PermissionHelper)->getUserUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Visit site_id must be set explicitly.');

        Visit::query()->create([
            'title' => 'Missing Site Visit',
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'scheduled_from' => now()->addDay(),
            'scheduled_until' => now()->addDay()->addHour(),
            'status' => VisitStatusEnum::Planned->value,
        ]);
    }

    public function test_recurring_visit_series_requires_explicit_site_id_on_create(): void
    {
        $host = (new PermissionHelper)->getUserUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Recurring visit series site_id must be set explicitly.');

        RecurringVisitSeries::query()->create([
            'title' => 'Missing Site Series',
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'created_by_user_id' => $host->id,
            'status' => VisitStatusEnum::Planned->value,
            'starts_at' => now()->addDay(),
            'duration_minutes' => 60,
            'frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
            'ends' => RecurringVisitSeries::END_COUNT,
            'occurrence_count' => 1,
        ]);
    }

    public function test_unused_monitor_resource_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('monitors.index'));
        $this->assertFalse(Route::has('monitors.create'));
        $this->assertFalse(Route::has('monitors.store'));
        $this->assertFalse(Route::has('monitors.destroy'));
        $this->assertFalse(Route::has('monitors.slides.index'));
        $this->assertFalse(Route::has('monitors.slides.show'));

        $this->assertTrue(Route::has('monitors.show'));
        $this->assertTrue(Route::has('monitors.edit'));
        $this->assertTrue(Route::has('monitors.update'));
        $this->assertTrue(Route::has('monitors.slides.create'));
        $this->assertTrue(Route::has('monitors.slides.store'));
        $this->assertTrue(Route::has('monitors.slides.edit'));
        $this->assertTrue(Route::has('monitors.slides.update'));
        $this->assertTrue(Route::has('monitors.slides.destroy'));
    }

    public function test_user_cannot_patch_foreign_visit(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $owner->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visit->visitors()->attach($visitor->id);

        $this->actingAs($user)
            ->patch(route('portal.visits.update', $visit), [
                'title' => 'Blocked Update',
                'host_user_id' => $owner->id,
                'substitute_user_id' => null,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_regular_user_create_page_does_not_expose_unrelated_visitor_directory(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        Visitor::factory()->create([
            'first_name' => 'Hidden',
            'name' => 'Visitor',
            'email' => 'hidden.visitor@example.com',
            'phone' => '+49 111 222333',
        ]);

        $this->actingAs($user)
            ->get(route('portal.visits.create'))
            ->assertOk()
            ->assertDontSee('Hidden Visitor')
            ->assertDontSee('hidden.visitor@example.com')
            ->assertDontSee('+49 111 222333');
    }

    public function test_regular_user_cannot_attach_hidden_visitor_by_id(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Visitor Attach',
                'host_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertSessionHasErrors('participants.0.visitor_id');
    }

    public function test_regular_user_does_not_reuse_or_disclose_hidden_visitor_by_contact_details(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create([
            'first_name' => 'Hidden',
            'name' => 'Visitor',
            'email' => 'hidden.contact@example.com',
            'phone' => '+49 444 555666',
        ]);

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Contact Reuse',
                'host_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Attempted',
                        'name' => 'Overwrite',
                        'email' => 'hidden.contact@example.com',
                        'phone' => '+49 444 555666',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visitors', [
            'id' => $visitor->id,
            'first_name' => 'Hidden',
            'name' => 'Visitor',
        ]);
        $this->assertDatabaseHas('visitors', [
            'first_name' => 'Attempted',
            'name' => 'Overwrite',
            'email' => 'hidden.contact@example.com',
            'phone' => '+49 444 555666',
        ]);
        $this->assertSame(2, Visitor::query()->where('email', 'hidden.contact@example.com')->count());
        $this->assertSame(2, Visitor::query()->where('phone', '+49 444 555666')->count());
    }

    public function test_regular_user_cannot_create_visit_for_another_host_or_completed_status(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $otherUser = (new PermissionHelper)->getUserUser();

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Host Status',
                'host_user_id' => $otherUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Completed->value,
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                    ],
                ],
            ])
            ->assertSessionHasErrors(['host_user_id', 'status']);
    }

    public function test_regular_user_cannot_assign_arbitrary_substitute_user(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $otherUser = (new PermissionHelper)->getUserUser();

        $this->actingAs($user)
            ->post(route('portal.visits.store'), [
                'title' => 'Blocked Substitute',
                'host_user_id' => $user->id,
                'substitute_user_id' => $otherUser->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    [
                        'first_name' => 'Anna',
                        'name' => 'Becker',
                    ],
                ],
            ])
            ->assertSessionHasErrors('substitute_user_id');
    }

    public function test_regular_user_cannot_update_visit_after_all_participants_checked_out(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create(['created_by_user_id' => $user->id]);
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visit->visitors()->attach($visitor->id, [
            'checked_in_at' => now()->subHours(2),
            'checked_out_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->patch(route('portal.visits.update', $visit), [
                'title' => 'Blocked Historical Update',
                'host_user_id' => $user->id,
                'scheduled_from' => now()->addDay()->format('Y-m-d H:i:s'),
                'scheduled_until' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => VisitStatusEnum::Planned->value,
                'participants' => [
                    ['visitor_id' => $visitor->id],
                ],
            ])
            ->assertForbidden();
    }

    public function test_regular_user_cannot_mount_reception_check_in_board(): void
    {
        $user = (new PermissionHelper)->getUserUser();

        Livewire::actingAs($user)
            ->test(CheckInOutBoard::class)
            ->assertForbidden();
    }

    public function test_site_reception_cannot_search_or_operate_on_other_site_visits(): void
    {
        $site = Site::factory()->create(['name' => 'Standort Ulm']);
        $otherSite = Site::factory()->create(['name' => 'Standort Neu-Ulm']);
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $host = (new PermissionHelper)->getUserUser();
        $otherHost = (new PermissionHelper)->getUserUser();

        $receptionist->forceFill(['site_id' => $site->id])->save();
        $host->forceFill(['site_id' => $site->id])->save();
        $otherHost->forceFill(['site_id' => $otherSite->id])->save();

        $visibleVisitor = Visitor::factory()->create(['first_name' => 'Visible', 'name' => 'Guest']);
        $hiddenVisitor = Visitor::factory()->create(['first_name' => 'Hidden', 'name' => 'Guest']);
        $visibleVisit = Visit::factory()->create([
            'site_id' => $site->id,
            'host_user_id' => $host->id,
            'substitute_user_id' => $receptionist->id,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $hiddenVisit = Visit::factory()->create([
            'site_id' => $otherSite->id,
            'host_user_id' => $otherHost->id,
            'substitute_user_id' => null,
            'scheduled_from' => now()->addHour(),
            'scheduled_until' => now()->addHours(2),
            'status' => VisitStatusEnum::Planned->value,
        ]);

        $visibleVisit->visitors()->attach($visibleVisitor);
        $hiddenVisit->visitors()->attach($hiddenVisitor);

        Livewire::actingAs($receptionist)
            ->test(CheckInOutBoard::class)
            ->set('search', 'Guest')
            ->assertSee('Visible Guest')
            ->assertDontSee('Hidden Guest')
            ->call('checkIn', $hiddenVisit->id, $hiddenVisitor->id)
            ->assertForbidden();
    }

    public function test_security_headers_keep_livewire_compatible_csp(): void
    {
        $response = $this->get(route('login'))->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    public function test_local_security_headers_allow_vite_dev_server(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->get(route('login'))->assertOk();
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline' http://localhost:5173", $csp);
        $this->assertStringContainsString("connect-src 'self' http://localhost:5173 ws://localhost:5173", $csp);
    }

    public function test_regular_user_cannot_call_reception_check_in_route(): void
    {
        $user = (new PermissionHelper)->getUserUser();
        $owner = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $owner->id,
            'substitute_user_id' => null,
        ]);
        $visit->visitors()->attach($visitor->id);

        $this->actingAs($user)
            ->post(route('reception.participants.check-in', [$visit, $visitor]))
            ->assertForbidden();
    }

    public function test_monitor_slide_cannot_be_deleted_through_wrong_monitor_parent(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $admin->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $monitor = Monitor::query()->create(['name' => 'Monitor A', 'auto_generation' => false]);
        $otherMonitor = Monitor::query()->create(['name' => 'Monitor B', 'auto_generation' => false]);
        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Scoped Slide',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
        ]);

        $this->actingAs($admin)
            ->withSession([
                'auth.method' => 'local',
                'auth.app_mfa_satisfied_at' => now()->timestamp,
                'auth.app_mfa_satisfied_method' => 'totp',
                'auth.app_mfa_satisfied_for_auth_method' => 'local',
            ])
            ->delete(route('monitors.slides.destroy', [$otherMonitor, $slide]))
            ->assertNotFound();

        $this->assertDatabaseHas('monitor_slides', ['id' => $slide->id]);
    }

    public function test_monitor_slide_delete_requires_parent_monitor_update_permission(): void
    {
        $user = (new PermissionHelper)->getIndividualUser(['Delete:MonitorSlide'], 'slide-deleter');
        $monitor = Monitor::query()->create(['name' => 'Monitor A', 'auto_generation' => false]);
        $slide = $monitor->monitorSlides()->create([
            'heading' => 'Scoped Slide',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('monitors.slides.destroy', [$monitor, $slide]))
            ->assertForbidden();

        $this->assertDatabaseHas('monitor_slides', ['id' => $slide->id]);
    }

    public function test_retention_cleans_monitor_slide_visitor_payloads(): void
    {
        $oldTimestamp = now()->subDays(400);
        $monitor = Monitor::query()->create(['name' => 'Monitor A', 'auto_generation' => false]);
        $visitor = Visitor::factory()->create([
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);
        $autoSlide = $monitor->monitorSlides()->create([
            'heading' => 'Auto',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => true,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => null, 'name' => 'Old Auto Visitor']],
        ]);
        $manualSlide = $monitor->monitorSlides()->create([
            'heading' => 'Manual',
            'slide_number' => 1,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => null, 'name' => 'Old Manual Visitor']],
        ]);
        $recentSlide = $monitor->monitorSlides()->create([
            'heading' => 'Recent',
            'slide_number' => 2,
            'is_active' => true,
            'is_auto_generated' => false,
            'show_logo' => true,
            'show_date' => true,
            'visitors' => [['id' => $visitor->id, 'name' => 'Expired Visitor']],
        ]);

        $autoSlide->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();
        $manualSlide->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();

        $this->artisan('visits:purge-expired', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('monitor_slides', ['id' => $autoSlide->id]);
        $this->assertNull($manualSlide->fresh()->visitors);
        $this->assertDatabaseMissing('visitors', ['id' => $visitor->id]);
        $this->assertNull($recentSlide->fresh()->visitors);
    }

    public function test_retention_purges_expired_recurring_series_and_pivot_notes(): void
    {
        $oldTimestamp = now()->subDays(400);
        $host = (new PermissionHelper)->getUserUser();
        $visitor = Visitor::factory()->create(['created_by_user_id' => $host->id]);
        $series = RecurringVisitSeries::query()->create([
            'title' => 'Expired recurring visit',
            'site_id' => $host->site_id,
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'created_by_user_id' => $host->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Old series note',
            'starts_at' => $oldTimestamp,
            'duration_minutes' => 60,
            'frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
            'ends' => RecurringVisitSeries::END_DATE,
            'end_date' => $oldTimestamp->toDateString(),
            'generated_until' => $oldTimestamp,
        ]);
        $series->visitors()->attach($visitor->id, ['notes' => 'Old visitor pivot note']);
        $series->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();

        $this->artisan('visits:purge-expired', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseMissing('recurring_visit_series', ['id' => $series->id]);
        $this->assertDatabaseMissing('recurring_visit_series_visitor', [
            'recurring_visit_series_id' => $series->id,
            'visitor_id' => $visitor->id,
        ]);
    }

    public function test_retention_does_not_delete_active_future_recurring_series(): void
    {
        $oldTimestamp = now()->subDays(400);
        $host = (new PermissionHelper)->getUserUser();
        $series = RecurringVisitSeries::query()->create([
            'title' => 'Active recurring visit',
            'site_id' => $host->site_id,
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'created_by_user_id' => $host->id,
            'status' => VisitStatusEnum::Planned->value,
            'notes' => 'Keep this series',
            'starts_at' => $oldTimestamp,
            'duration_minutes' => 60,
            'frequency' => RecurringVisitSeries::FREQUENCY_WEEKLY,
            'ends' => RecurringVisitSeries::END_FOREVER,
            'generated_until' => $oldTimestamp,
        ]);
        Visit::factory()->create([
            'recurring_visit_series_id' => $series->id,
            'recurrence_occurrence_number' => 999,
            'host_user_id' => $host->id,
            'substitute_user_id' => null,
            'scheduled_from' => now()->addDay(),
            'scheduled_until' => now()->addDay()->addHour(),
        ]);
        $series->forceFill(['created_at' => $oldTimestamp, 'updated_at' => $oldTimestamp])->save();

        $this->artisan('visits:purge-expired', ['--days' => 365])->assertExitCode(0);

        $this->assertDatabaseHas('recurring_visit_series', ['id' => $series->id]);
    }

    public function test_retention_purges_old_retention_run_logs(): void
    {
        $oldRun = DataRetentionRun::query()->create([
            'command' => 'visits:purge-expired',
            'dry_run' => false,
            'retention_days' => 365,
            'cutoff_at' => now()->subDays(365),
            'status' => 'completed',
            'started_at' => now()->subYears(4),
            'finished_at' => now()->subYears(4),
        ]);
        $oldRun->forceFill([
            'created_at' => now()->subDays(1200),
            'updated_at' => now()->subDays(1200),
        ])->save();

        $this->artisan('visits:purge-expired')->assertExitCode(0);

        $this->assertDatabaseMissing('data_retention_runs', ['id' => $oldRun->id]);
    }
}
