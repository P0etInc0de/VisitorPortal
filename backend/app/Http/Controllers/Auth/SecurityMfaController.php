<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AppMfaChallenge;
use App\Support\AppMfaSession;
use App\Support\AuthRedirector;
use App\Support\MfaOnboarding;
use App\Support\SecurityEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

final class SecurityMfaController extends Controller
{
    public function required(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterConfiguredMfa($request);
        }

        return view('security.mfa.required', [
            'user' => $request->user(),
        ]);
    }

    public function setup(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterConfiguredMfa($request);
        }

        if (blank($request->user()->two_factor_secret)) {
            $enable($request->user());
            $request->user()->refresh();
        }

        return view('security.mfa.setup', [
            'user' => $request->user(),
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('security.mfa.recovery-codes');
        }

        $enable($request->user());

        return Redirect::route('security.mfa.setup')
            ->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED);
    }

    public function confirm(
        Request $request,
        ConfirmTwoFactorAuthentication $confirm,
        GenerateNewRecoveryCodes $generateRecoveryCodes,
    ): RedirectResponse {
        $confirm($request->user(), $request->input('code'));

        $request->user()->refresh();

        if (blank($request->user()->two_factor_recovery_codes)) {
            $generateRecoveryCodes($request->user());
        }

        app(AppMfaSession::class)->markSatisfied(
            $request,
            'totp',
            app(MfaOnboarding::class)->authMethod($request)
        );
        app(SecurityEventLogger::class)->log($request, 'security_app_mfa_step_up_completed', [
            'step_up_purpose' => 'required-mfa-onboarding',
            'step_up_method' => 'totp',
        ]);

        return Redirect::route('security.mfa.recovery-codes')
            ->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }

    public function challenge(Request $request): RedirectResponse|View
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::to(app(AuthRedirector::class)->pathFor($request->user()));
        }

        $appMfa = app(AppMfaSession::class);
        $authMethod = $appMfa->challengeAuthMethod($request);

        if ($appMfa->isSatisfied($request, $authMethod)) {
            return $this->redirectAfterChallenge($request);
        }

        return view('security.mfa.challenge', [
            'action' => route('security.mfa.challenge.verify'),
            'title' => __('Zwei-Faktor-Authentifizierung bestätigen'),
            'description' => __('Bitte bestätige den Zugriff mit dem sechsstelligen Code aus deiner Authenticator-App.'),
            'button' => __('Zugriff bestätigen'),
            'showLogout' => true,
            'backUrl' => null,
            'allowedMethods' => ['totp', 'recovery_code'],
        ]);
    }

    public function verifyChallenge(Request $request, AppMfaChallenge $challenge): RedirectResponse
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::to(app(AuthRedirector::class)->pathFor($request->user()));
        }

        $appMfa = app(AppMfaSession::class);
        $authMethod = $appMfa->challengeAuthMethod($request);
        $method = $challenge->verify($request, ['totp', 'recovery_code'], 'app-mfa-session');

        $appMfa->markSatisfied($request, $method, $authMethod);
        app(SecurityEventLogger::class)->log($request, 'security_app_mfa_step_up_completed', [
            'step_up_purpose' => 'app-mfa-session',
            'step_up_method' => $method,
        ]);

        return $this->redirectAfterChallenge($request);
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generateRecoveryCodes): RedirectResponse|View
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('security.mfa.required')
                ->with('warning', __('Recovery Codes sind erst nach bestätigter Zwei-Faktor-Authentifizierung verfügbar.'));
        }

        if (blank($request->user()->two_factor_recovery_codes)) {
            $generateRecoveryCodes($request->user());
            $request->user()->refresh();
        }

        app(SecurityEventLogger::class)->log($request, 'security_recovery_codes_viewed', [
            'step_up_purpose' => 'required-mfa-onboarding',
            'step_up_method' => 'totp',
        ]);

        return view('security.mfa.recovery-codes', [
            'user' => $request->user(),
            'recoveryCodes' => $request->user()->recoveryCodes(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        return $this->redirectAfterMfa($request);
    }

    private function redirectAfterMfa(Request $request): RedirectResponse
    {
        $onboarding = app(MfaOnboarding::class);
        $intendedUrl = $onboarding->intendedUrl($request);

        $onboarding->clear($request);

        return Redirect::to(app(AuthRedirector::class)->intendedUrlOrDefault(
            $request,
            $request->user(),
            $intendedUrl,
        ));
    }

    private function redirectAfterConfiguredMfa(Request $request): RedirectResponse
    {
        $appMfa = app(AppMfaSession::class);
        $onboarding = app(MfaOnboarding::class);
        $authMethod = $onboarding->authMethod($request);

        if ($appMfa->isSatisfied($request, $authMethod)) {
            return $this->redirectAfterMfa($request);
        }

        $appMfa->startChallenge(
            $request,
            $authMethod,
            $onboarding->intendedUrl($request) ?? url(app(AuthRedirector::class)->pathFor($request->user()))
        );

        return Redirect::route('security.mfa.challenge');
    }

    private function redirectAfterChallenge(Request $request): RedirectResponse
    {
        $appMfa = app(AppMfaSession::class);
        $intendedUrl = $appMfa->intendedUrl($request);

        $appMfa->clearChallenge($request);

        return Redirect::to(app(AuthRedirector::class)->intendedUrlOrDefault(
            $request,
            $request->user(),
            $intendedUrl,
        ));
    }
}
