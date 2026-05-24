<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Auth;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\SsoAuthenticationException;
use App\Auth\Sso\SsoUserResolver;
use App\Http\Controllers\Controller;
use App\Support\AppMfaSession;
use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
use App\Support\UserSessionPreferences;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class OidcAuthController extends Controller
{
    public function redirect(OidcAuthenticator $oidc): RedirectResponse
    {
        $this->abortIfSsoUnavailable();

        return $oidc->redirect();
    }

    public function callback(
        Request $request,
        OidcAuthenticator $oidc,
        SsoUserResolver $resolver,
    ): RedirectResponse {
        $this->abortIfSsoUnavailable();

        try {
            $identity = $oidc->authenticateCallback($request);
            $user = $resolver->resolve($identity);
        } catch (SsoAuthenticationException|AuthenticationException $exception) {
            Log::channel('web')->warning('sso_login_failed', [
                'reason' => $exception->getMessage(),
                'route' => $request->route()?->getName(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => __('Single Sign-On failed. Please contact your administrator.'),
                ]);
        }

        Auth::login($user, remember: false);

        $request->session()->regenerate();
        $request->session()->put('auth.method', 'sso');
        $request->session()->put('auth.oidc.issuer', $identity->issuer);
        $request->session()->put('auth.oidc.subject', $identity->subject);
        app(AppMfaSession::class)->clear($request);

        app(UserSessionPreferences::class)->apply($request, $user);

        Log::channel('web')->info('sso_login_success', [
            'user_id' => $user->id,
            'issuer' => $identity->issuer,
            'subject_hash' => hash_hmac('sha256', $identity->subject, (string) config('app.key')),
            'email_domain' => $identity->email !== null ? str($identity->email)->afterLast('@')->lower()->toString() : null,
            'provisioning_mode' => config('sso.oidc.provisioning_mode'),
            'role_sync_enabled' => (bool) config('sso.oidc.sync_roles', false),
        ]);

        $redirector = app(AuthRedirector::class);

        if ($user->requiresTwoFactorAuthentication('sso')) {
            $intendedUrl = $redirector->intendedUrlOrDefault(
                $request,
                $user,
                $request->session()->pull('url.intended')
            );

            if (! $user->hasConfirmedTwoFactorAuthentication()) {
                app(MfaOnboarding::class)->start($request, 'sso', $intendedUrl);

                return redirect()
                    ->route('security.mfa.required')
                    ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um fortzufahren.'));
            }

            app(AppMfaSession::class)->startChallenge($request, 'sso', $intendedUrl);

            return redirect()
                ->route('security.mfa.challenge')
                ->with('warning', __('Bitte bestätige die lokale Zwei-Faktor-Authentifizierung, um fortzufahren.'));
        }

        return redirect()->to($redirector->intendedUrlOrDefault(
            $request,
            $user,
            $request->session()->pull('url.intended'),
        ));
    }

    private function abortIfSsoUnavailable(): void
    {
        abort_unless((bool) config('sso.enabled'), 404);
        abort_unless(config('sso.driver') === 'oidc', 404);
        abort_unless(in_array(config('sso.auth_mode'), ['local_and_sso', 'sso_only'], true), 404);
    }
}
