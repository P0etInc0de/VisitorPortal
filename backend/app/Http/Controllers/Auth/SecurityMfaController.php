<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AppMfaChallenge;
use App\Support\AppMfaSession;
use App\Support\MfaOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

final class SecurityMfaController extends Controller
{
    public function required(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterConfiguredMfa($request);
        }

        return view('security.mfa.required', [
            'user' => $request->user(),
        ]);
    }

    public function setup(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterConfiguredMfa($request);
        }

        return view('security.mfa.setup', [
            'user' => $request->user(),
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('security.mfa.recovery-codes');
        }

        $enable($request->user());

        return Redirect::route('security.mfa.setup')
            ->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_ENABLED);
    }

    public function confirm(
        Request $request,
        ConfirmTwoFactorAuthentication $confirm,
        GenerateNewRecoveryCodes $generateRecoveryCodes,
    ): RedirectResponse {
        $confirm($request->user(), $request->input('code'));

        $request->user()->refresh();

        if (blank($request->user()->two_factor_recovery_codes)) {
            $generateRecoveryCodes($request->user());
        }

        app(AppMfaSession::class)->markSatisfied(
            $request,
            'totp',
            app(MfaOnboarding::class)->authMethod($request)
        );

        return Redirect::route('security.mfa.recovery-codes')
            ->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
    }

    public function challenge(Request $request): RedirectResponse|View
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('overview');
        }

        $appMfa = app(AppMfaSession::class);
        $authMethod = $appMfa->challengeAuthMethod($request);

        if ($appMfa->isSatisfied($request, $authMethod)) {
            return $this->redirectAfterChallenge($request);
        }

        return view('security.mfa.challenge', [
            'action' => route('security.mfa.challenge.verify'),
            'title' => __('Zwei-Faktor-Authentifizierung bestaetigen'),
            'description' => __('Bitte bestaetige den Zugriff mit dem sechsstelligen Code aus deiner Authenticator-App oder einem Recovery Code.'),
            'button' => __('Zugriff bestaetigen'),
            'showLogout' => true,
            'backUrl' => null,
        ]);
    }

    public function verifyChallenge(Request $request, AppMfaChallenge $challenge): RedirectResponse
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('overview');
        }

        $appMfa = app(AppMfaSession::class);
        $authMethod = $appMfa->challengeAuthMethod($request);
        $method = $challenge->verify($request);

        $appMfa->markSatisfied($request, $method, $authMethod);

        return $this->redirectAfterChallenge($request);
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generateRecoveryCodes): RedirectResponse|View
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('security.mfa.required')
                ->with('warning', __('Recovery Codes sind erst nach bestaetigter MFA verfuegbar.'));
        }

        if (blank($request->user()->two_factor_recovery_codes)) {
            $generateRecoveryCodes($request->user());
            $request->user()->refresh();
        }

        return view('security.mfa.recovery-codes', [
            'user' => $request->user(),
            'recoveryCodes' => $request->user()->recoveryCodes(),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        return $this->redirectAfterMfa($request);
    }

    private function redirectAfterMfa(Request $request): RedirectResponse
    {
        $onboarding = app(MfaOnboarding::class);
        $intendedUrl = $onboarding->intendedUrl($request);

        $onboarding->clear($request);

        return $intendedUrl
            ? Redirect::to($intendedUrl)
            : Redirect::route('overview');
    }

    private function redirectAfterConfiguredMfa(Request $request): RedirectResponse
    {
        $appMfa = app(AppMfaSession::class);
        $onboarding = app(MfaOnboarding::class);
        $authMethod = $onboarding->authMethod($request);

        if ($appMfa->isSatisfied($request, $authMethod)) {
            return $this->redirectAfterMfa($request);
        }

        $appMfa->startChallenge($request, $authMethod, $onboarding->intendedUrl($request) ?? route('overview'));

        return Redirect::route('security.mfa.challenge');
    }

    private function redirectAfterChallenge(Request $request): RedirectResponse
    {
        $appMfa = app(AppMfaSession::class);
        $intendedUrl = $appMfa->intendedUrl($request);

        $appMfa->clearChallenge($request);

        return $intendedUrl
            ? Redirect::to($intendedUrl)
            : Redirect::route('overview');
    }
}
