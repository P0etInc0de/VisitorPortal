<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AppMfaChallenge;
use App\Support\AppMfaSession;
use App\Support\SensitiveActionConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

final class SecurityStepUpController extends Controller
{
    public function show(Request $request, string $purpose): RedirectResponse|View
    {
        $this->abortIfUnsupportedPurpose($purpose);

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Zwei-Faktor-Authentifizierung muss zuerst eingerichtet sein.'));
        }

        $confirmation = app(SensitiveActionConfirmation::class);

        if ($confirmation->isFresh($request, $purpose)) {
            return Redirect::to($confirmation->intendedUrl($request) ?? route('profile.security.recovery-codes'));
        }

        return view('security.mfa.challenge', [
            'action' => route('security.step-up.verify', $purpose),
            'title' => __('Sicherheitsbestaetigung erforderlich'),
            'description' => __('Bitte bestaetige deine Identitaet mit deiner Authenticator-App oder einem Recovery Code, bevor diese Sicherheitsdaten angezeigt werden.'),
            'button' => __('Bestaetigen'),
            'showLogout' => false,
            'backUrl' => route('profile.security'),
        ]);
    }

    public function verify(
        Request $request,
        string $purpose,
        AppMfaChallenge $challenge,
    ): RedirectResponse {
        $this->abortIfUnsupportedPurpose($purpose);

        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('profile.security')
                ->with('warning', __('Zwei-Faktor-Authentifizierung muss zuerst eingerichtet sein.'));
        }

        $method = $challenge->verify($request);
        $authMethod = (string) $request->session()->get('auth.method', 'local');

        app(AppMfaSession::class)->markSatisfied($request, $method, $authMethod);

        $confirmation = app(SensitiveActionConfirmation::class);
        $confirmation->markConfirmed($request, $purpose, $method);

        $intendedUrl = $confirmation->intendedUrl($request) ?? route('profile.security.recovery-codes');

        $confirmation->clearPrompt($request);

        return Redirect::to($intendedUrl);
    }

    private function abortIfUnsupportedPurpose(string $purpose): void
    {
        abort_unless($purpose === 'recovery-codes', 404);
    }
}
