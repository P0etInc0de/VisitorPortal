<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SecurityEventLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(Request $request, string $event, array $context = []): void
    {
        Log::channel('web')->info($event, [
            'user_id' => $request->user()?->getAuthIdentifier(),
            'auth_method' => (string) $request->session()->get('auth.method', 'local'),
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'user_agent_hash' => hash_hmac('sha256', (string) $request->userAgent(), (string) config('app.key')),
            ...$context,
        ]);
    }
}
