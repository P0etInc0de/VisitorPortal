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
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelMfa
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

        if (! app(AppMfaPolicy::class)->isRequiredForAdminPanel($user, $authMethod)) {
            return $next($request);
        }

        $intendedUrl = $request->isMethod('GET')
            ? $request->fullUrl()
            : url(app(AuthRedirector::class)->pathFor($user));

        if ($user->hasConfirmedTwoFactorAuthentication()) {
            $appMfa = app(AppMfaSession::class);

            if ($appMfa->isSatisfied($request, $authMethod)) {
                return $next($request);
            }

            $appMfa->startChallenge($request, $authMethod, $intendedUrl);

            return redirect()
                ->route('security.mfa.challenge')
                ->with('warning', __('Bitte bestätige die lokale Zwei-Faktor-Authentifizierung, um den Adminbereich zu nutzen.'));
        }

        app(MfaOnboarding::class)->start(
            $request,
            $authMethod,
            $intendedUrl
        );

        return redirect()
            ->route('security.mfa.required')
            ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um den Adminbereich zu nutzen.'));
    }
}
