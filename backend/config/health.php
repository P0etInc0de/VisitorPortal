<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [
    'cache_store' => env('HEALTH_CACHE_STORE', 'database'),

    'queue_heartbeat_key' => 'health:queue:last_loop',
    'scheduler_heartbeat_key' => 'health:scheduler:last_run',

    // Must be comfortably larger than the queue worker timeout.
    'queue_stale_after_seconds' => (int) env('HEALTH_QUEUE_STALE_AFTER', 300),

    // Scheduler runs every minute; allow operational jitter.
    'scheduler_stale_after_seconds' => (int) env('HEALTH_SCHEDULER_STALE_AFTER', 300),

    'heartbeat_ttl_seconds' => (int) env('HEALTH_HEARTBEAT_TTL', 600),
];
