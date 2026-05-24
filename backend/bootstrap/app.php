<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

use App\Http\Middleware\ApplyUserPreferences;
use App\Http\Middleware\EnsureRequiredMfaIsConfigured;
use App\Http\Middleware\NormalizeMfaCodeInputs;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        }

        $middleware->web(append: [
            ApplyUserPreferences::class,
            NormalizeMfaCodeInputs::class,
            SecurityHeaders::class,
            EnsureRequiredMfaIsConfigured::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
