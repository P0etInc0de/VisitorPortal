<?php

namespace App\Support;

use Illuminate\Http\Request;

final class SensitiveActionConfirmation
{
    private const FRESH_FOR_SECONDS = 600;

    public function start(Request $request, string $purpose, string $intendedUrl): void
    {
        $request->session()->put([
            'security.step_up.purpose' => $purpose,
            'security.step_up.intended_url' => $intendedUrl,
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
}
