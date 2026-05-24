<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Console;

use App\Support\OperationalHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VisitorPortalHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('health.cache_store', 'database');
        config()->set('health.queue_stale_after_seconds', 300);
        config()->set('health.scheduler_stale_after_seconds', 300);
        config()->set('health.heartbeat_ttl_seconds', 600);

        Cache::store('database')->forget(config('health.queue_heartbeat_key'));
        Cache::store('database')->forget(config('health.scheduler_heartbeat_key'));
    }

    public function test_health_app_returns_success_in_healthy_environment(): void
    {
        $this->artisan('visitorportal:health app')
            ->expectsOutput('OK app')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_health_queue_returns_failure_when_heartbeat_is_missing(): void
    {
        $this->artisan('visitorportal:health queue')
            ->expectsOutput('FAIL queue: heartbeat is stale')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_health_queue_returns_success_when_heartbeat_is_fresh(): void
    {
        app(OperationalHeartbeat::class)->markQueueLoop();

        $this->artisan('visitorportal:health queue')
            ->expectsOutput('OK queue')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_health_scheduler_returns_failure_when_heartbeat_is_missing(): void
    {
        $this->artisan('visitorportal:health scheduler')
            ->expectsOutput('FAIL scheduler: heartbeat is stale')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_health_scheduler_returns_success_when_heartbeat_is_fresh(): void
    {
        app(OperationalHeartbeat::class)->markSchedulerRun();

        $this->artisan('visitorportal:health scheduler')
            ->expectsOutput('OK scheduler')
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_health_command_output_does_not_expose_env_secrets(): void
    {
        config()->set('app.key', 'health-app-key-secret');
        config()->set('database.connections.mysql.password', 'health-db-password-secret');
        config()->set('sso.oidc.client_secret', 'health-oidc-client-secret');

        $exitCode = Artisan::call('visitorportal:health', ['target' => 'queue']);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringNotContainsString('APP_KEY', $output);
        $this->assertStringNotContainsString('DB_PASSWORD', $output);
        $this->assertStringNotContainsString('OIDC_CLIENT_SECRET', $output);
        $this->assertStringNotContainsString('health-app-key-secret', $output);
        $this->assertStringNotContainsString('health-db-password-secret', $output);
        $this->assertStringNotContainsString('health-oidc-client-secret', $output);
    }
}
