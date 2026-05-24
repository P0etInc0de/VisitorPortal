<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Providers;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\Facile\FacileOidcAuthenticator;
use App\Support\MfaCodeNormalizer;
use App\Support\OperationalHeartbeat;
use App\Support\RasterImageUpload;
use App\Support\SafeLogContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OidcAuthenticator::class, FacileOidcAuthenticator::class);
    }

    /**
     * Bootstrap any application services.
     * Includes logging for failed authorizations/inadequate permissions.
     */
    public function boot(): void
    {
        if (config('livewire.temporary_file_upload.rules') === null) {
            config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.RasterImageUpload::maxSizeKilobytes()]]);
        }

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));
            $key = ($email !== '' ? $email : 'anonymous').'|'.$request->ip();

            return Limit::perSecond(
                max(1, (int) config('auth.password_reset_rate_limit.max_attempts', 6)),
                max(1, (int) config('auth.password_reset_rate_limit.decay_seconds', 60)),
            )->by($key);
        });

        RateLimiter::for('two-factor', function (Request $request): Limit {
            $loginId = (string) ($request->session()->get('login.id')
                ?? $request->user()?->getAuthIdentifier()
                ?? 'anonymous');

            return Limit::perMinute(5)->by($loginId.'|'.$request->ip());
        });

        Queue::looping(function (): void {
            try {
                app(OperationalHeartbeat::class)->markQueueLoop();
            } catch (\Throwable $exception) {
                report($exception);
            }
        });

        Event::listen(TwoFactorAuthenticationFailed::class, function (TwoFactorAuthenticationFailed $event): void {
            $request = request();

            Log::channel('web')->info('security_app_mfa_challenge_failed', [
                'user_id' => $event->user?->getAuthIdentifier(),
                'auth_method' => 'local',
                'step_up_purpose' => 'login',
                'attempted_method' => $this->attemptedMfaMethod($request),
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent_hash' => hash_hmac('sha256', (string) $request->userAgent(), (string) config('app.key')),
            ]);
        });

        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false) {
                Log::channel('web')->info('Authorization failed.', SafeLogContext::authorization($user, $ability, $arguments));
            }
        });
    }

    private function attemptedMfaMethod(Request $request): string
    {
        $normalizer = app(MfaCodeNormalizer::class);
        $hasTotpInput = $normalizer->hasInput($request->input('code'));
        $hasRecoveryCodeInput = $normalizer->hasInput($request->input('recovery_code'));

        return match (true) {
            $hasTotpInput && $hasRecoveryCodeInput => 'mixed',
            $hasRecoveryCodeInput => 'recovery_code',
            $hasTotpInput => 'totp',
            default => 'unknown',
        };
    }
}
