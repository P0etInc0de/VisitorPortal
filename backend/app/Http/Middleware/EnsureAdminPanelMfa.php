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

        if (! $user || ! config('security.mfa.enabled')) {
            return $next($request);
        }

        $authMethod = $request->hasSession()
            ? (string) $request->session()->get('auth.method', 'local')
            : 'local';

        $enforcedMethods = config('security.mfa.app_required_for_admin_panel_auth_methods', ['local', 'sso']);

        if (! in_array($authMethod, $enforcedMethods, true)
            || ! $user->hasAnyRole(config('security.mfa.app_required_roles', ['admin', 'super_admin']))
            || $user->hasConfirmedTwoFactorAuthentication()) {
            return $next($request);
        }

        return redirect()
            ->route('profile.security')
            ->with('warning', __('Bitte richte die Zwei-Faktor-Authentifizierung ein, um den Adminbereich zu nutzen.'));
    }
}
