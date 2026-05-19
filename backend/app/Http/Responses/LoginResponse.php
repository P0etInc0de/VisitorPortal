<?php

namespace App\Http\Responses;

use App\Support\AppMfaSession;
use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
use App\Support\SafeRedirectUrl;
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
            $method = $request->filled('recovery_code') ? 'recovery_code' : 'totp';

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

        if ($user->requiresTwoFactorAuthentication($authMethod) && ! $user->hasConfirmedTwoFactorAuthentication()) {
            app(MfaOnboarding::class)->start(
                $request,
                $authMethod,
                $request->session()->pull('url.intended', url(app(AuthRedirector::class)->pathFor($user)))
            );

            return redirect()
                ->route('security.mfa.required')
                ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
        }

        $redirectUrl = app(SafeRedirectUrl::class)->sanitize(
            $request,
            $request->session()->pull('url.intended'),
            url(app(AuthRedirector::class)->pathFor($user))
        );

        $response = redirect()->to($redirectUrl);

        if (($method ?? null) === 'recovery_code') {
            $response->with('warning', __('Ein Recovery Code wurde verwendet und ersetzt. Bitte prüfe deine neuen Recovery Codes.'));
        }

        return $response;
    }
}
