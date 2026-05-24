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
use App\Support\SafeRedirectUrl;
use App\Support\SecurityEventLogger;
use App\Support\SensitiveActionConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

final class SecurityStepUpController extends Controller
{
    public function show(Request $request, string $purpose): RedirectResponse|View
    {
        $this->abortIfUnsupportedPurpose($purpose);

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Zwei-Faktor-Authentifizierung muss zuerst eingerichtet sein.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);

        if ($confirmation->isFresh($request, $purpose)) {
            return Redirect::to(app(SafeRedirectUrl::class)->sanitize(
                $request,
                $confirmation->intendedUrl($request),
                route('overview')
            ));
        }

        return view('security.mfa.challenge', [
            'action' => route('security.step-up.verify', $purpose),
            'title' => __('Sicherheitsbestätigung erforderlich'),
            'description' => $this->descriptionFor($purpose),
            'button' => __('Bestätigen'),
            'showLogout' => false,
            'backUrl' => route('profile.security'),
            'allowedMethods' => $this->allowedMethodsFor($purpose),
        ]);
    }

    public function verify(
        Request $request,
        string $purpose,
        AppMfaChallenge $challenge,
        GenerateNewRecoveryCodes $generateRecoveryCodes,
    ): RedirectResponse {
        $this->abortIfUnsupportedPurpose($purpose);

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Zwei-Faktor-Authentifizierung muss zuerst eingerichtet sein.'));
        }

        $method = $challenge->verify($request, $this->allowedMethodsFor($purpose), $purpose);
        $authMethod = (string) $request->session()->get('auth.method', 'local');

        app(AppMfaSession::class)->markSatisfied($request, $method, $authMethod);

        $confirmation = app(SensitiveActionConfirmation::class);
        $confirmation->markConfirmed($request, $purpose, $method);
        app(SecurityEventLogger::class)->log($request, 'security_app_mfa_step_up_completed', [
            'step_up_purpose' => $purpose,
            'step_up_method' => $method,
        ]);

        if ($purpose === SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE) {
            $generateRecoveryCodes($request->user());
            app(SecurityEventLogger::class)->log($request, 'security_recovery_codes_regenerated', [
                'step_up_purpose' => $purpose,
                'step_up_method' => $method,
            ]);
            $confirmation->clearConfirmation($request, $purpose);
            $confirmation->clearPrompt($request);

            return Redirect::route('profile.security.recovery-codes')
                ->with('status', Fortify::RECOVERY_CODES_GENERATED)
                ->with('security.recovery_codes.just_regenerated', true)
                ->with('security.recovery_codes.step_up_method', $method);
        }

        $intendedUrl = app(SafeRedirectUrl::class)->sanitize(
            $request,
            $confirmation->intendedUrl($request),
            route('overview')
        );

        $confirmation->clearPrompt($request);

        return Redirect::to($intendedUrl);
    }

    private function abortIfUnsupportedPurpose(string $purpose): void
    {
        abort_unless(in_array($purpose, [
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE,
        ], true), 404);
    }

    /**
     * @return list<string>
     */
    private function allowedMethodsFor(string $purpose): array
    {
        return ['totp'];
    }

    private function descriptionFor(string $purpose): string
    {
        if ($purpose === SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW) {
            return __('Aus Sicherheitsgründen musst du deine Zwei-Faktor-Authentifizierung mit deiner Authenticator-App erneut bestätigen, bevor deine Recovery Codes angezeigt werden.');
        }

        return __('Bestätige diese Änderung mit deiner Authenticator-App. Danach werden deine alten Recovery Codes ersetzt.');
    }
}
