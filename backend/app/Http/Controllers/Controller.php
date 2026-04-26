<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    protected function authorizeAny(array $abilities, mixed $model): void
    {
        foreach ($abilities as $ability) {
            if (Gate::allows($ability, $model)) {
                return;
            }
        }

        abort(403);
    }
	
	protected function authorizeDashboard(): void
	{
		if (Gate::any(['ViewAny:Visit', 'ViewDepartment:Visit', 'ViewOwn:Visit'])) {
			return;
		}
		
		abort(403);
	}
}
