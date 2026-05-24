<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Http\Request;

class AuthRedirector
{
    public function pathFor(User $user): string
    {
        if ($user->hasAnyRole(['welcome monitor', 'welcome_monitor'])) {
            $siteMonitor = Monitor::query()
                ->whereIn('site_id', $user->assignedSiteIds()->all())
                ->orderByRaw('CASE WHEN site_id = ? THEN 0 ELSE 1 END', [(int) $user->site_id])
                ->first();

            return $siteMonitor
                ? route('monitors.show', $siteMonitor, absolute: false)
                : route('monitors.missing', absolute: false);
        }

        if ($user->hasRole('receptionist')) {
            return route('reception.dashboard', absolute: false);
        }

        return route('overview', absolute: false);
    }

    public function intendedUrlOrDefault(Request $request, User $user, ?string $intendedUrl): string
    {
        $defaultUrl = url($this->pathFor($user));
        $url = app(SafeRedirectUrl::class)->sanitize($request, $intendedUrl, $defaultUrl);

        return $this->canUseIntendedUrl($user, $url, $defaultUrl)
            ? $url
            : $defaultUrl;
    }

    private function canUseIntendedUrl(User $user, string $url, string $defaultUrl): bool
    {
        if ($url === $defaultUrl) {
            return true;
        }

        $path = $this->pathFromUrl($url);

        if ($path === '' || $this->isAuthFlowPath($path)) {
            return false;
        }

        if ($path === 'overview') {
            return $this->canViewOverview($user);
        }

        if ($path === 'reception' || str_starts_with($path, 'reception/')) {
            return $user->hasRole('receptionist');
        }

        return true;
    }

    private function pathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return trim(is_string($path) ? $path : '', '/');
    }

    private function isAuthFlowPath(string $path): bool
    {
        return in_array($path, [
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'confirm-password',
            'confirmed-password-status',
            'verify-email',
        ], true)
            || str_starts_with($path, 'auth/oidc')
            || str_starts_with($path, 'security/mfa')
            || str_starts_with($path, 'security/step-up')
            || str_starts_with($path, 'two-factor')
            || str_starts_with($path, 'user/two-factor');
    }

    private function canViewOverview(User $user): bool
    {
        return $user->can('ViewAny:Visit')
            || $user->can('ViewSite:Visit')
            || $user->can('ViewDepartment:Visit')
            || $user->can('ViewOwn:Visit');
    }
}
