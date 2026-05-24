<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Visitor Contact Requirement
    |--------------------------------------------------------------------------
    |
    | Supported values: optional, require_one, require_email, require_phone.
    | The privacy-friendly default keeps e-mail and phone optional.
    |
    */
    'visitor_contact_requirement' => env('VISITOR_CONTACT_REQUIREMENT', 'optional'),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Visits older than this amount of days are eligible for purging unless a
    | legal hold is active. Dry-run mode uses the same cutoff without deleting.
    |
    */
    'visit_retention_days' => (int) env('PRIVACY_VISIT_RETENTION_DAYS', 365),
    'purge_enabled' => (bool) env('PRIVACY_PURGE_ENABLED', true),
    'purge_chunk_size' => (int) env('PRIVACY_PURGE_CHUNK_SIZE', 500),
    'notification_retention_days' => (int) env('PRIVACY_NOTIFICATION_RETENTION_DAYS', 365),
    'search_rate_limit_per_minute' => (int) env('PRIVACY_SEARCH_RATE_LIMIT_PER_MINUTE', 60),
    'walk_in_confidential_default' => (bool) env('PRIVACY_WALK_IN_CONFIDENTIAL_DEFAULT', true),
    'notice_url' => env('PRIVACY_NOTICE_URL'),
    'run_log_retention_days' => (int) env('DATA_RETENTION_RUN_LOG_DAYS', 1095),

    'technical_retention' => [
        'enabled' => (bool) env('PRIVACY_TECHNICAL_RETENTION_ENABLED', true),
        'session_days' => (int) env('PRIVACY_SESSION_RETENTION_DAYS', 30),
        'failed_job_days' => (int) env('PRIVACY_FAILED_JOB_RETENTION_DAYS', 30),
        'job_batch_days' => (int) env('PRIVACY_JOB_BATCH_RETENTION_DAYS', 30),
        'log_days' => (int) env('PRIVACY_LOG_RETENTION_DAYS', 30),
        'health_cache_days' => (int) env('PRIVACY_HEALTH_CACHE_RETENTION_DAYS', 7),
        'log_path' => storage_path('logs'),
    ],
];
