<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Middleware;

use App\Support\AppMfaPolicy;
use App\Support\AppMfaSession;
use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
use App\Support\SecurityEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequiredMfaIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.recovery-codes', 'two-factor.regenerate-recovery-codes')) {
            abort(403, __('Recovery Codes werden nur über die Sicherheitsseite mit frischer Bestätigung verwaltet.'));
        }

        if (! config('security.mfa.enabled')) {
            return $next($request);
        }

        $authMethod = $request->hasSession()
            ? (string) $request->session()->get('auth.method', 'local')
            : 'local';

        $policy = app(AppMfaPolicy::class);

        if ($request->routeIs('two-factor.enable')
            && ! config('security.mfa.optional_for_users')
            && ! $policy->isRequiredForAnyContext($user)) {
            abort(403, __('Zwei-Faktor-Authentifizierung kann für dieses Konto aktuell nicht selbst aktiviert werden.'));
        }

        if ($request->routeIs('two-factor.disable') && $policy->isRequiredForAnyContext($user)) {
            abort(403, __('Zwei-Faktor-Authentifizierung kann für verpflichtende Sicherheitskontexte nicht deaktiviert werden.'));
        }

        if ($request->routeIs('two-factor.disable')) {
            $response = $next($request);

            if ($response->isRedirection() || $response->isSuccessful()) {
                app(SecurityEventLogger::class)->log($request, 'security_app_mfa_disabled');
            }

            return $response;
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
            abort(403, __('Bitte bestätige die Zwei-Faktor-Authentifizierung, um fortzufahren.'));
        }

        $appMfa->startChallenge($request, $authMethod, $this->intendedUrl($request));

        return redirect()
            ->route('security.mfa.challenge')
            ->with('warning', __('Bitte bestätige die Zwei-Faktor-Authentifizierung, um fortzufahren.'));
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
                : (string) $request->session()->get('security.mfa.intended_url', $this->defaultUrl($request))
        );
    }

    private function intendedUrl(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return $request->fullUrl();
        }

        $url = $request->session()->get('security.mfa.challenge_intended_url');

        return is_string($url) && $url !== '' ? $url : $this->defaultUrl($request);
    }

    private function defaultUrl(Request $request): string
    {
        $user = $request->user();

        return $user ? url(app(AuthRedirector::class)->pathFor($user)) : route('overview');
    }
}
