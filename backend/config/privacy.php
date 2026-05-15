<?php

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
];
