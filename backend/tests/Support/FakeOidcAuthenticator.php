<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Support;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\DTO\OidcIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class FakeOidcAuthenticator implements OidcAuthenticator
{
    public function __construct(
        private readonly ?OidcIdentity $identity = null,
    ) {}

    public function redirect(): RedirectResponse
    {
        return redirect()->away('/fake-oidc-provider');
    }

    public function authenticateCallback(Request $request): OidcIdentity
    {
        return $this->identity ?? new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: 'sso.user@example.org',
            emailVerified: true,
            displayName: 'Sso User',
            groups: [],
            claims: [
                'iss' => 'https://idp.example.test',
                'sub' => 'subject-123',
                'email' => 'sso.user@example.org',
                'email_verified' => true,
                'name' => 'Sso User',
            ],
        );
    }

    public function logoutRedirectUrl(?string $idTokenHint = null): ?string
    {
        return null;
    }
}
