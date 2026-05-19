<?php

namespace App\Support;

use Illuminate\Http\Request;

final class AppMfaSession
{
    public function markSatisfied(Request $request, string $method, ?string $authMethod = null): void
    {
        $request->session()->put([
            'auth.app_mfa_satisfied_at' => now()->timestamp,
            'auth.app_mfa_satisfied_method' => $method,
            'auth.app_mfa_satisfied_for_auth_method' => $authMethod ?? $this->authMethod($request),
        ]);
    }

    public function isSatisfied(Request $request, ?string $authMethod = null): bool
    {
        $satisfiedAt = $request->session()->get('auth.app_mfa_satisfied_at');
        $satisfiedFor = $request->session()->get('auth.app_mfa_satisfied_for_auth_method');

        return is_int($satisfiedAt)
            && $satisfiedFor === ($authMethod ?? $this->authMethod($request));
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            'auth.app_mfa_satisfied_at',
            'auth.app_mfa_satisfied_method',
            'auth.app_mfa_satisfied_for_auth_method',
            'security.mfa.challenge_auth_method',
            'security.mfa.challenge_intended_url',
        ]);
    }

    public function startChallenge(Request $request, string $authMethod, string $intendedUrl): void
    {
        $request->session()->put([
            'security.mfa.challenge_auth_method' => $authMethod,
            'security.mfa.challenge_intended_url' => $intendedUrl,
        ]);
    }

    public function challengeAuthMethod(Request $request): string
    {
        return (string) $request->session()->get('security.mfa.challenge_auth_method', $this->authMethod($request));
    }

    public function intendedUrl(Request $request): ?string
    {
        $url = $request->session()->get('security.mfa.challenge_intended_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    public function clearChallenge(Request $request): void
    {
        $request->session()->forget([
            'security.mfa.challenge_auth_method',
            'security.mfa.challenge_intended_url',
        ]);
    }

    private function authMethod(Request $request): string
    {
        return (string) $request->session()->get('auth.method', 'local');
    }
}
