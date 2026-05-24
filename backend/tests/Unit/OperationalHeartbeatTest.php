<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use App\Support\OperationalHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OperationalHeartbeatTest extends TestCase
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

    public function test_mark_queue_loop_writes_heartbeat_in_health_cache_store(): void
    {
        app(OperationalHeartbeat::class)->markQueueLoop();

        $this->assertIsString(
            Cache::store('database')->get(config('health.queue_heartbeat_key'))
        );
    }

    public function test_queue_is_fresh_is_true_for_fresh_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.queue_heartbeat_key'),
            now()->toISOString(),
            now()->addSeconds(600)
        );

        $this->assertTrue(app(OperationalHeartbeat::class)->queueIsFresh());
    }

    public function test_queue_is_fresh_is_false_for_missing_timestamp(): void
    {
        $this->assertFalse(app(OperationalHeartbeat::class)->queueIsFresh());
    }

    public function test_queue_is_fresh_is_false_for_old_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.queue_heartbeat_key'),
            now()->subSeconds(301)->toISOString(),
            now()->addSeconds(600)
        );

        $this->assertFalse(app(OperationalHeartbeat::class)->queueIsFresh());
    }

    public function test_queue_is_fresh_is_false_for_invalid_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.queue_heartbeat_key'),
            'not-a-date',
            now()->addSeconds(600)
        );

        $this->assertFalse(app(OperationalHeartbeat::class)->queueIsFresh());
    }

    public function test_mark_scheduler_run_writes_heartbeat_in_health_cache_store(): void
    {
        app(OperationalHeartbeat::class)->markSchedulerRun();

        $this->assertIsString(
            Cache::store('database')->get(config('health.scheduler_heartbeat_key'))
        );
    }

    public function test_scheduler_is_fresh_is_true_for_fresh_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.scheduler_heartbeat_key'),
            now()->toISOString(),
            now()->addSeconds(600)
        );

        $this->assertTrue(app(OperationalHeartbeat::class)->schedulerIsFresh());
    }

    public function test_scheduler_is_fresh_is_false_for_missing_timestamp(): void
    {
        $this->assertFalse(app(OperationalHeartbeat::class)->schedulerIsFresh());
    }

    public function test_scheduler_is_fresh_is_false_for_old_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.scheduler_heartbeat_key'),
            now()->subSeconds(301)->toISOString(),
            now()->addSeconds(600)
        );

        $this->assertFalse(app(OperationalHeartbeat::class)->schedulerIsFresh());
    }

    public function test_scheduler_is_fresh_is_false_for_invalid_timestamp(): void
    {
        Cache::store('database')->put(
            config('health.scheduler_heartbeat_key'),
            'not-a-date',
            now()->addSeconds(600)
        );

        $this->assertFalse(app(OperationalHeartbeat::class)->schedulerIsFresh());
    }
}
