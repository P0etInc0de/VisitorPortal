<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! config('security.mfa.enabled') || ! config('security.mfa.required_for_admin_panel')) {
            return $next($request);
        }

        if (! $user->hasAnyRole(['admin', 'super_admin']) || $user->hasConfirmedTwoFactorAuthentication()) {
            return $next($request);
        }

        return redirect()
            ->route('profile.security')
            ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um den Adminbereich zu nutzen.'));
    }
}
