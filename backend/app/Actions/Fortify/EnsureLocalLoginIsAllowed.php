<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class EnsureLocalLoginIsAllowed
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly LoginRateLimiter $limiter,
    ) {}

    public function handle($request, $next)
    {
        $provider = $this->guard->getProvider();
        $user = $provider->retrieveByCredentials($request->only(Fortify::username(), 'password'));

        if (! $user || ! $provider->validateCredentials($user, ['password' => $request->password])) {
            return $next($request);
        }

        if ($user instanceof User && ! $user->canLoginLocally()) {
            $this->limiter->increment($request);

            throw ValidationException::withMessages([
                Fortify::username() => __('Local login is disabled for this account. Please use SSO.'),
            ]);
        }

        return $next($request);
    }
}
