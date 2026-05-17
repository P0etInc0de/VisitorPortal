<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredMfaIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! config('security.mfa.enabled')) {
            return $next($request);
        }

        $authMethod = $request->hasSession()
            ? (string) $request->session()->get('auth.method', 'local')
            : 'local';

        if ($request->routeIs('two-factor.enable')
            && ! config('security.mfa.optional_for_users')
            && ! $user->requiresTwoFactorAuthentication($authMethod)) {
            abort(403, __('MFA kann fuer dieses Konto aktuell nicht selbst aktiviert werden.'));
        }

        if ($request->routeIs('two-factor.disable') && $user->requiresTwoFactorAuthentication($authMethod)) {
            abort(403, __('MFA kann fuer privilegierte Rollen nicht dauerhaft deaktiviert werden.'));
        }

        if (! $user->requiresTwoFactorAuthentication($authMethod) || $user->hasConfirmedTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($this->isAllowedMfaSetupRoute($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
        }

        return redirect()
            ->route('profile.security')
            ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
    }

    private function isAllowedMfaSetupRoute(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'profile.security',
            'profile.security.*',
            'password.confirm',
            'password.confirm.*',
            'password.confirmation',
            'two-factor.*'
        ) || $request->is(
            'user/two-factor-authentication',
            'user/confirmed-two-factor-authentication',
            'user/two-factor-qr-code',
            'user/two-factor-secret-key',
            'user/two-factor-recovery-codes'
        );
    }
}
