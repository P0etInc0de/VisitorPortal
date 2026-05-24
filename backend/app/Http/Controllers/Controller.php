<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers;

use App\Support\SafeLogContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    protected function authorizeAny(array $abilities, mixed $model): void
    {
        if (! Gate::any($abilities, $model)) {
            Log::channel('web')->info('Authorization failed.', SafeLogContext::authorization(auth()->user(), implode('|', $abilities), $model));

            abort(403);
        }
    }

    protected function authorizeDashboard(): void
    {
        if (! Gate::any(['ViewAny:Visit', 'ViewSite:Visit', 'ViewDepartment:Visit', 'ViewOwn:Visit'])) {
            Log::channel('web')->info('Authorization failed.', [
                'user_id' => auth()->id(),
                'ability' => 'viewDashboard',
                'resource_type' => 'Visit',
                'resource_id' => null,
            ]);

            abort(403);
        }
    }
}
