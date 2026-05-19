<?php

namespace App\Http\Middleware;

use App\Support\AppMfaSession;
use App\Support\MfaOnboarding;
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

        if (! $user->requiresTwoFactorAuthentication($authMethod)) {
            return $next($request);
        }

        if (! $user->hasConfirmedTwoFactorAuthentication()) {
            if ($this->isAllowedMfaSetupRoute($request)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                abort(403, __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
            }

            $this->startMfaOnboarding($request, $authMethod);

            return redirect()
                ->route('security.mfa.required')
                ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
        }

        $appMfa = app(AppMfaSession::class);

        if ($appMfa->isSatisfied($request, $authMethod) || $this->isAllowedMfaSetupRoute($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('Bitte bestaetige die Zwei-Faktor-Authentifizierung, um fortzufahren.'));
        }

        $appMfa->startChallenge($request, $authMethod, $this->intendedUrl($request));

        return redirect()
            ->route('security.mfa.challenge')
            ->with('warning', __('Bitte bestaetige die Zwei-Faktor-Authentifizierung, um fortzufahren.'));
    }

    private function isAllowedMfaSetupRoute(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'security.mfa.*',
            'security.step-up.*',
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

    private function startMfaOnboarding(Request $request, string $authMethod): void
    {
        app(MfaOnboarding::class)->start(
            $request,
            $authMethod,
            $request->isMethod('GET')
                ? $request->fullUrl()
                : (string) $request->session()->get('security.mfa.intended_url', route('overview'))
        );
    }

    private function intendedUrl(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return $request->fullUrl();
        }

        $url = $request->session()->get('security.mfa.challenge_intended_url');

        return is_string($url) && $url !== '' ? $url : route('overview');
    }
}
