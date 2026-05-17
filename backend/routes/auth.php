<?php

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\OidcAuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ThemePreferenceController;
use App\Http\Controllers\Auth\VerifyEmailController;
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
        ->middleware('password.confirm')
        ->name('profile.security.recovery-codes');

    Route::get('profile/security/two-factor-setup', [ProfileController::class, 'twoFactorSetup'])
        ->middleware('password.confirm')
        ->name('profile.security.two-factor-setup');

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
