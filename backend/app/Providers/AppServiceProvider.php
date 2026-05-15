<?php

namespace App\Providers;

use App\Support\SafeLogContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Includes logging for failed authorizations/inadequate permissions.
     */
    public function boot(): void
    {
        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));
            $key = ($email !== '' ? $email : 'anonymous').'|'.$request->ip();

            return Limit::perSecond(
                max(1, (int) config('auth.password_reset_rate_limit.max_attempts', 6)),
                max(1, (int) config('auth.password_reset_rate_limit.decay_seconds', 60)),
            )->by($key);
        });

        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false) {
                Log::channel('web')->info('Authorization failed.', SafeLogContext::authorization($user, $ability, $arguments));
            }
        });
    }
}
