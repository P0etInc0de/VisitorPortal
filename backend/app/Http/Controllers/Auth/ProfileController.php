<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\SensitiveActionConfirmation;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

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

        return view('profile.security', [
            'user' => $request->user(),
        ]);
    }

    public function recoveryCodes(Request $request): RedirectResponse|View
    {
        $this->authorize('view', $request->user());

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Recovery Codes sind erst nach bestaetigter MFA verfuegbar.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);

        if (! $confirmation->isFresh($request, 'recovery-codes')) {
            $confirmation->start($request, 'recovery-codes', $request->fullUrl());

            return Redirect::route('security.step-up.show', 'recovery-codes');
        }

        return view('profile.recovery-codes', [
            'user' => $request->user(),
            'recoveryCodes' => $request->user()->recoveryCodes(),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generateRecoveryCodes): RedirectResponse
    {
        $this->authorize('view', $request->user());

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Recovery Codes sind erst nach bestaetigter MFA verfuegbar.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);

        if (! $confirmation->isFresh($request, 'recovery-codes')) {
            $confirmation->start($request, 'recovery-codes', route('profile.security.recovery-codes'));

            return Redirect::route('security.step-up.show', 'recovery-codes');
        }

        $generateRecoveryCodes($request->user());

        return Redirect::route('profile.security.recovery-codes')
            ->with('status', Fortify::RECOVERY_CODES_GENERATED);
    }

    public function twoFactorSetup(Request $request): RedirectResponse|View
    {
        $this->authorize('view', $request->user());

        if (blank($request->user()->two_factor_secret)) {
            return Redirect::route('profile.security')
                ->with('warning', __('Bitte starte die MFA-Einrichtung zuerst.'));
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
