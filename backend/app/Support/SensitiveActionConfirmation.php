<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Http\Request;

final class SensitiveActionConfirmation
{
    public const PURPOSE_RECOVERY_CODES_VIEW = 'recovery-codes:view';

    public const PURPOSE_RECOVERY_CODES_REGENERATE = 'recovery-codes:regenerate';

    private const FRESH_FOR_SECONDS = 600;

    public function start(Request $request, string $purpose, string $intendedUrl): void
    {
        $request->session()->put([
            'security.step_up.purpose' => $purpose,
            'security.step_up.intended_url' => app(SafeRedirectUrl::class)->sanitize($request, $intendedUrl),
        ]);
    }

    public function markConfirmed(Request $request, string $purpose, string $method): void
    {
        $request->session()->put([
            'security.step_up.confirmed_at' => now()->timestamp,
            'security.step_up.method' => $method,
            'security.step_up.confirmed_for' => $purpose,
        ]);
    }

    public function isFresh(Request $request, string $purpose): bool
    {
        $confirmedAt = $request->session()->get('security.step_up.confirmed_at');

        return is_int($confirmedAt)
            && $request->session()->get('security.step_up.confirmed_for') === $purpose
            && $confirmedAt >= now()->subSeconds(self::FRESH_FOR_SECONDS)->timestamp;
    }

    public function intendedUrl(Request $request): ?string
    {
        $url = $request->session()->get('security.step_up.intended_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function clearPrompt(Request $request): void
    {
        $request->session()->forget([
            'security.step_up.purpose',
            'security.step_up.intended_url',
        ]);
    }

    public function clearConfirmation(Request $request, ?string $purpose = null): void
    {
        if ($purpose !== null && $request->session()->get('security.step_up.confirmed_for') !== $purpose) {
            return;
        }

        $request->session()->forget([
            'security.step_up.confirmed_at',
            'security.step_up.method',
            'security.step_up.confirmed_for',
        ]);
    }
}
