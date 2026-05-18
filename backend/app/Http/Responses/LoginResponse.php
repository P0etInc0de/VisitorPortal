<?php

namespace App\Http\Responses;

use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
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

        return redirect()->intended(
            app(AuthRedirector::class)->pathFor($user)
        );
    }
}
