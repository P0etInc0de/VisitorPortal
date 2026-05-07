<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    protected function authorizeAny(array $abilities, mixed $model): void
    {
        foreach ($abilities as $ability) {
            if (! Gate::allows($ability, $model)) {
                Log::channel('web')->info('Authorization failed.', [
                    'user_id' => auth()->id(),
                    'ability' => $ability,
                    'model' => $model,
                    'url' => request()->fullUrl(),
                    'ip' => request()->ip(),
                ]);

                abort(403);
            }
        }
    }

    protected function authorizeDashboard(): void
    {
        if (! Gate::any(['ViewAny:Visit', 'ViewDepartment:Visit', 'ViewOwn:Visit'])) {
            Log::channel('web')->info('Authorization failed.', [
                'user_id' => auth()->id(),
                'permissions' => ['ViewOwn:Visit', 'ViewDepartment:Visit', 'ViewAny:Visit'],
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
            ]);

            abort(403);
        }
    }
}
