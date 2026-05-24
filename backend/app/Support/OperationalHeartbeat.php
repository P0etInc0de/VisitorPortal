<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class OperationalHeartbeat
{
    public function markQueueLoop(): void
    {
        $this->put(config('health.queue_heartbeat_key'));
    }

    public function markSchedulerRun(): void
    {
        $this->put(config('health.scheduler_heartbeat_key'));
    }

    public function queueIsFresh(): bool
    {
        return $this->isFresh(
            config('health.queue_heartbeat_key'),
            (int) config('health.queue_stale_after_seconds')
        );
    }

    public function schedulerIsFresh(): bool
    {
        return $this->isFresh(
            config('health.scheduler_heartbeat_key'),
            (int) config('health.scheduler_stale_after_seconds')
        );
    }

    private function put(string $key): void
    {
        Cache::store(config('health.cache_store'))->put(
            $key,
            now()->toISOString(),
            now()->addSeconds((int) config('health.heartbeat_ttl_seconds'))
        );
    }

    private function isFresh(string $key, int $staleAfterSeconds): bool
    {
        $value = Cache::store(config('health.cache_store'))->get($key);

        if (! is_string($value) || $value === '') {
            return false;
        }

        try {
            $timestamp = Carbon::parse($value);
        } catch (Throwable) {
            return false;
        }

        return $timestamp->greaterThanOrEqualTo(
            now()->subSeconds($staleAfterSeconds)
        );
    }
}
