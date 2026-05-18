<?php

namespace App\Http\Middleware;

use App\Support\MfaOnboarding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMfaOnboardingIsAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $onboarding = app(MfaOnboarding::class);

        if (! $onboarding->isFresh($request)) {
            $onboarding->clear($request);

            return redirect()->route('overview');
        }

        if ($user->hasConfirmedTwoFactorAuthentication()) {
            return $next($request);
        }

        if (! $onboarding->isRequiredFor($user, $request)) {
            $onboarding->clear($request);

            return redirect()->route('overview');
        }

        return $next($request);
    }
}
