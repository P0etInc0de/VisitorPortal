<?php

namespace App\Providers;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\Facile\FacileOidcAuthenticator;
use App\Support\OperationalHeartbeat;
use App\Support\SafeLogContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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
        $this->app->bind(OidcAuthenticator::class, FacileOidcAuthenticator::class);
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

        RateLimiter::for('two-factor', function (Request $request): Limit {
            $loginId = (string) $request->session()->get('login.id', 'anonymous');

            return Limit::perMinute(5)->by($loginId.'|'.$request->ip());
        });

        Queue::looping(function (): void {
            try {
                app(OperationalHeartbeat::class)->markQueueLoop();
            } catch (\Throwable $exception) {
                report($exception);
            }
        });

        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false) {
                Log::channel('web')->info('Authorization failed.', SafeLogContext::authorization($user, $ability, $arguments));
            }
        });
    }
}
