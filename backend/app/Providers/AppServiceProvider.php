<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
		Gate::after(function ($user, $ability, $result, $arguments) {
   			if ($result === false) {
        		Log::channel('web')->info('Authorization failed.', [
            		'user_id'   => $user?->id,
            		'ability'   => $ability,
            		'arguments' => $arguments,
					'url'        => request()->fullUrl(),
	                'ip'         => request()->ip(),
        		]);
    		}
		});
	}
}
