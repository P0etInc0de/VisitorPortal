<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Responses;

use App\Support\SafeRedirectUrl;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorConfirmedResponse as TwoFactorConfirmedResponseContract;
use Laravel\Fortify\Fortify;

final class TwoFactorConfirmedResponse implements TwoFactorConfirmedResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 200);
        }

        $intendedUrl = $request->session()->pull('security.mfa.intended_url');

        if (is_string($intendedUrl) && $intendedUrl !== '') {
            return redirect()->to(app(SafeRedirectUrl::class)->sanitize($request, $intendedUrl))
                ->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
        }

        return back()->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }
}
