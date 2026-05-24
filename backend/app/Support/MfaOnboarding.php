<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class MfaOnboarding
{
    private const FRESH_FOR_SECONDS = 600;

    public function start(Request $request, string $authMethod, string $intendedUrl): void
    {
        $request->session()->put([
            'security.mfa.onboarding_required' => true,
            'security.mfa.authenticated_at' => now()->timestamp,
            'security.mfa.auth_method' => $authMethod,
            'security.mfa.intended_url' => app(SafeRedirectUrl::class)->sanitize($request, $intendedUrl),
        ]);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            'security.mfa.onboarding_required',
            'security.mfa.authenticated_at',
            'security.mfa.auth_method',
            'security.mfa.intended_url',
        ]);
    }

    public function isFresh(Request $request): bool
    {
        if ($request->session()->get('security.mfa.onboarding_required') !== true) {
            return false;
        }

        $authenticatedAt = $request->session()->get('security.mfa.authenticated_at');

        return is_int($authenticatedAt)
            && $authenticatedAt >= now()->subSeconds(self::FRESH_FOR_SECONDS)->timestamp;
    }

    public function authMethod(Request $request): string
    {
        return (string) $request->session()->get(
            'security.mfa.auth_method',
            $request->session()->get('auth.method', 'local')
        );
    }

    public function intendedUrl(Request $request): ?string
    {
        $url = $request->session()->get('security.mfa.intended_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function isRequiredFor(User $user, Request $request): bool
    {
        $authMethod = $this->authMethod($request);

        return $user->requiresTwoFactorAuthentication($authMethod)
            || $this->adminPanelMfaIsRequired($user, $authMethod, $this->intendedUrl($request));
    }

    private function adminPanelMfaIsRequired(User $user, string $authMethod, ?string $intendedUrl): bool
    {
        if (! $this->isAdminPanelUrl($intendedUrl)) {
            return false;
        }

        return app(AppMfaPolicy::class)->isRequiredForAdminPanel($user, $authMethod);
    }

    private function isAdminPanelUrl(?string $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === 'admin' || str_starts_with($path, 'admin/');
    }
}
