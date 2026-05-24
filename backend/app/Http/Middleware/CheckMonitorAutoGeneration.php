<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMonitorAutoGeneration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $monitor = $request->route('monitor');

        if ($monitor && $monitor->auto_generation == 1) {
            return redirect()->route('monitors.edit', $monitor)
                ->with('warning', __('Sie können auf die Monitor Seiten nicht zugreifen solange die Auto-Generierung aktiv ist'));
        } else {
            return $next($request);
        }
    }
}
