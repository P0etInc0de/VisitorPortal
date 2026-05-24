<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Http\Request;

final class SafeRedirectUrl
{
    public function sanitize(Request $request, ?string $url, ?string $fallback = null): string
    {
        $fallback ??= route('overview');

        if (! is_string($url) || trim($url) === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return $fallback;
        }

        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')) {
            return $url;
        }

        $parts = parse_url($url);

        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return $fallback;
        }

        if (! isset($parts['scheme']) && ! isset($parts['host'])) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHosts = array_filter([
            strtolower($request->getHost()),
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
        ]);

        if (in_array($scheme, ['http', 'https'], true) && in_array($host, $allowedHosts, true)) {
            return $url;
        }

        return $fallback;
    }
}
