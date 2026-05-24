<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\AppMfaPolicy;
use App\Support\RecoveryCodeManager;
use App\Support\SecurityEventLogger;
use App\Support\SensitiveActionConfirmation;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $this->authorize('view', $request->user());

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function security(Request $request): View
    {
        $this->authorize('view', $request->user());
        $authMethod = (string) $request->session()->get('auth.method', 'local');
        $mfaPolicy = app(AppMfaPolicy::class);

        return view('profile.security', [
            'user' => $request->user(),
            'authMethod' => $authMethod,
            'mfaRequiredForLogin' => $mfaPolicy->isRequiredForAuthMethod($request->user(), $authMethod),
            'mfaRequiredForAdminPanel' => $mfaPolicy->isRequiredForAdminPanel($request->user(), $authMethod),
            'mfaRequiredForAnyContext' => $mfaPolicy->isRequiredForAnyContext($request->user()),
        ]);
    }

    public function recoveryCodes(Request $request): RedirectResponse|View
    {
        $this->authorize('view', $request->user());

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Recovery Codes sind erst nach bestätigter Zwei-Faktor-Authentifizierung verfügbar.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);
        $regenerationStepUpMethod = $request->session()->pull('security.recovery_codes.step_up_method', 'unknown');
        $allowedAfterRegeneration = $request->session()->pull('security.recovery_codes.just_regenerated', false) === true
            && $regenerationStepUpMethod === 'totp';

        if (! $allowedAfterRegeneration && ! $confirmation->isFresh($request, SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW)) {
            $confirmation->start($request, SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW, $request->fullUrl());

            return Redirect::route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW);
        }

        app(SecurityEventLogger::class)->log($request, 'security_recovery_codes_viewed', [
            'step_up_purpose' => $allowedAfterRegeneration
                ? SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE
                : SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            'step_up_method' => $allowedAfterRegeneration
                ? (string) $regenerationStepUpMethod
                : (string) $request->session()->get('security.step_up.method', 'unknown'),
        ]);

        $recoveryCodes = app(RecoveryCodeManager::class)->activeCodes($request->user());
        $confirmation->clearConfirmation($request, SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW);

        return view('profile.recovery-codes', [
            'user' => $request->user(),
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $this->authorize('view', $request->user());

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Recovery Codes sind erst nach bestätigter Zwei-Faktor-Authentifizierung verfügbar.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);
        $confirmation->start($request, SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE, route('profile.security.recovery-codes'));

        return Redirect::route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE);
    }

    public function twoFactorSetup(Request $request): RedirectResponse|View
    {
        $this->authorize('view', $request->user());

        if (blank($request->user()->two_factor_secret)) {
            return Redirect::route('profile.security')
                ->with('warning', __('Bitte starte die Zwei-Faktor-Einrichtung zuerst.'));
        }

        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security');
        }

        return view('profile.two-factor-setup', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());

        $request->user()->fill($request->validated());
        $request->user()->save();

        $locale = UserPreferences::normalizeLocale($request->input('locale'))
            ?? UserPreferences::normalizeLocale($request->user()->locale)
            ?? UserPreferences::browserLocale($request);
        $themePreference = UserPreferences::normalizeTheme($request->input('theme_preference'))
            ?? UserPreferences::normalizeTheme($request->user()->theme_preference)
            ?? UserPreferences::THEME_SYSTEM;

        $request->session()->put('locale', $locale);
        Cookie::queue(UserPreferences::LOCALE_COOKIE, $locale, 60 * 24 * 365);
        Cookie::queue(UserPreferences::THEME_COOKIE, $themePreference, 60 * 24 * 365);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403);
    }
}
