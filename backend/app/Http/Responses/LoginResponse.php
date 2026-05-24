<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Responses;

use App\Support\AppMfaSession;
use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
use App\Support\SecurityEventLogger;
use App\Support\UserSessionPreferences;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Dieses Benutzerkonto ist deaktiviert.'),
            ]);
        }

        $authMethod = 'local';
        $request->session()->put('auth.method', $authMethod);

        if ($request->routeIs('two-factor.login.store')) {
            $method = $request->session()->pull('auth.two_factor_login_method', 'totp') === 'recovery_code'
                ? 'recovery_code'
                : 'totp';

            app(AppMfaSession::class)->markSatisfied(
                $request,
                $method,
                $authMethod
            );
            app(SecurityEventLogger::class)->log($request, 'security_app_mfa_step_up_completed', [
                'step_up_purpose' => 'login',
                'step_up_method' => $method,
            ]);

            if ($method === 'recovery_code') {
                app(SecurityEventLogger::class)->log($request, 'security_recovery_code_used', [
                    'step_up_purpose' => 'login',
                    'step_up_method' => 'recovery_code',
                ]);
            }
        } else {
            app(AppMfaSession::class)->clear($request);
        }

        app(UserSessionPreferences::class)->apply($request, $user);

        $redirector = app(AuthRedirector::class);

        if ($user->requiresTwoFactorAuthentication($authMethod) && ! $user->hasConfirmedTwoFactorAuthentication()) {
            app(MfaOnboarding::class)->start(
                $request,
                $authMethod,
                $redirector->intendedUrlOrDefault($request, $user, $request->session()->pull('url.intended'))
            );

            return redirect()
                ->route('security.mfa.required')
                ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
        }

        $redirectUrl = $redirector->intendedUrlOrDefault(
            $request,
            $user,
            $request->session()->pull('url.intended'),
        );

        $response = redirect()->to($redirectUrl);

        if (($method ?? null) === 'recovery_code') {
            $response->with('warning', __('Ein Recovery Code wurde verwendet und aus deiner Liste entfernt. Bitte prüfe deine verbleibenden Recovery Codes oder erzeuge neue.'));
        }

        return $response;
    }
}
