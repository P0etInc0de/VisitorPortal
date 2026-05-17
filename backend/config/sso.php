<?php

return [
    'enabled' => env('SSO_ENABLED', false),
    'default_provider' => env('SSO_DEFAULT_PROVIDER', 'entra'),
    'auto_provision_users' => env('SSO_AUTO_PROVISION_USERS', false),
    'link_by_verified_email' => env('SSO_LINK_BY_VERIFIED_EMAIL', false),

    'providers' => [
        'entra' => [
            'enabled' => env('SSO_ENTRA_ENABLED', false),
            'tenant_id' => env('SSO_ENTRA_TENANT_ID'),
            'client_id' => env('SSO_ENTRA_CLIENT_ID'),
        ],
    ],
];
