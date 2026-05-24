<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso\Contracts;

use App\Auth\Sso\DTO\OidcIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface OidcAuthenticator
{
    public function redirect(): RedirectResponse;

    public function authenticateCallback(Request $request): OidcIdentity;

    public function logoutRedirectUrl(?string $idTokenHint = null): ?string;
}
