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
        'required_roles' => $csv(env('MFA_REQUIRED_ROLES'), 'admin,super_admin'),
        'required_for_admin_panel' => env('MFA_REQUIRED_FOR_ADMIN_PANEL', true),
        'enforced_auth_methods' => $csv(env('MFA_ENFORCED_AUTH_METHODS'), 'local,sso'),
    ],
];
