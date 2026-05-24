<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\OidcAuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\SecurityMfaController;
use App\Http\Controllers\Auth\SecurityStepUpController;
use App\Http\Controllers\Auth\ThemePreferenceController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Middleware\EnsureMfaOnboardingIsAllowed;
use App\Http\Middleware\PreventSensitivePageCaching;
use App\Support\SensitiveActionConfirmation;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('auth/oidc/redirect', [OidcAuthController::class, 'redirect'])
        ->name('auth.oidc.redirect');

    Route::get('auth/oidc/callback', [OidcAuthController::class, 'callback'])
        ->name('auth.oidc.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::get('profile/security', [ProfileController::class, 'security'])->name('profile.security');

    Route::get('profile/security/recovery-codes', [ProfileController::class, 'recoveryCodes'])
        ->middleware(PreventSensitivePageCaching::class)
        ->name('profile.security.recovery-codes');

    Route::post('profile/security/recovery-codes/regenerate', [ProfileController::class, 'regenerateRecoveryCodes'])
        ->name('profile.security.recovery-codes.regenerate');

    Route::get('profile/security/two-factor-setup', [ProfileController::class, 'twoFactorSetup'])
        ->middleware('password.confirm')
        ->name('profile.security.two-factor-setup');

    Route::middleware(EnsureMfaOnboardingIsAllowed::class)->group(function () {
        Route::get('security/mfa/required', [SecurityMfaController::class, 'required'])
            ->name('security.mfa.required');

        Route::get('security/mfa/setup', [SecurityMfaController::class, 'setup'])
            ->name('security.mfa.setup');

        Route::post('security/mfa/enable', [SecurityMfaController::class, 'enable'])
            ->name('security.mfa.enable');

        Route::post('security/mfa/confirm', [SecurityMfaController::class, 'confirm'])
            ->name('security.mfa.confirm');

        Route::get('security/mfa/recovery-codes', [SecurityMfaController::class, 'recoveryCodes'])
            ->middleware(PreventSensitivePageCaching::class)
            ->name('security.mfa.recovery-codes');

        Route::match(['get', 'post'], 'security/mfa/continue', [SecurityMfaController::class, 'complete'])
            ->name('security.mfa.continue');
    });

    Route::get('security/mfa/challenge', [SecurityMfaController::class, 'challenge'])
        ->name('security.mfa.challenge');

    Route::post('security/mfa/challenge', [SecurityMfaController::class, 'verifyChallenge'])
        ->middleware('throttle:two-factor')
        ->name('security.mfa.challenge.verify');

    Route::get('security/step-up/{purpose}', [SecurityStepUpController::class, 'show'])
        ->whereIn('purpose', [
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE,
        ])
        ->name('security.step-up.show');

    Route::post('security/step-up/{purpose}', [SecurityStepUpController::class, 'verify'])
        ->middleware('throttle:two-factor')
        ->whereIn('purpose', [
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE,
        ])
        ->name('security.step-up.verify');

    Route::patch('profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::patch('profile/theme-preference', [ThemePreferenceController::class, 'update'])
        ->name('profile.theme-preference.update');

    Route::delete('profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::put('password', [PasswordController::class, 'update'])->name('profile.password.update');
});
