<?php

$csv = static function (?string $value, string $default): array {
    return array_values(array_filter(
        array_map('trim', explode(',', $value ?: $default)),
        static fn (string $item): bool => $item !== ''
    ));
};

return [
    'mfa' => [
        'enabled' => env('MFA_ENABLED', true),
        'optional_for_users' => env('MFA_OPTIONAL_FOR_USERS', true),
        'app_required_roles' => $csv(env('APP_MFA_REQUIRED_ROLES'), 'admin,super_admin'),
        'app_required_for_auth_methods' => $csv(env('APP_MFA_REQUIRED_FOR_AUTH_METHODS'), 'local'),
        'app_required_for_admin_panel_auth_methods' => $csv(env('APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS'), 'local,sso'),
    ],
];
