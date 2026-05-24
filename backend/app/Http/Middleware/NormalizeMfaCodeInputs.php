<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Middleware;

use App\Support\MfaCodeNormalizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class NormalizeMfaCodeInputs
{
    public function __construct(private MfaCodeNormalizer $normalizer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isMfaRoute($request)) {
            $this->mergeNormalizedInput($request, 'code', 'normalizeTotp');
            $this->mergeNormalizedInput($request, 'recovery_code', 'normalizeRecoveryCode');
        }

        return $next($request);
    }

    /**
     * @param  'normalizeTotp'|'normalizeRecoveryCode'  $method
     */
    private function mergeNormalizedInput(Request $request, string $key, string $method): void
    {
        if (! $request->has($key)) {
            return;
        }

        $normalized = $this->normalizer->{$method}($request->input($key));

        if ($normalized !== null) {
            $request->merge([$key => $normalized]);
        }
    }

    private function isMfaRoute(Request $request): bool
    {
        return $request->routeIs(
            'two-factor.login.store',
            'two-factor.confirm',
            'security.mfa.confirm',
            'security.mfa.challenge.verify',
            'security.step-up.verify',
        );
    }
}
