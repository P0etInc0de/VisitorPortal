<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

final class SecurityMfaController extends Controller
{
    public function required(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterMfa($request);
        }

        return view('security.mfa.required', [
            'user' => $request->user(),
        ]);
    }

    public function setup(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasConfirmedTwoFactorAuthentication()) {
            return $this->redirectAfterMfa($request);
        }

        return view('security.mfa.setup', [
            'user' => $request->user(),
        ]);
    }

    public function recoveryCodes(Request $request): RedirectResponse|View
    {
        if (! $request->user()->hasConfirmedTwoFactorAuthentication()) {
            return Redirect::route('security.mfa.required')
                ->with('warning', __('Recovery Codes sind erst nach bestaetigter MFA verfuegbar.'));
        }

        return view('security.mfa.recovery-codes', [
            'user' => $request->user(),
            'recoveryCodes' => $request->user()->recoveryCodes(),
        ]);
    }

    private function redirectAfterMfa(Request $request): RedirectResponse
    {
        $intendedUrl = $request->session()->pull('security.mfa.intended_url');

        return $intendedUrl
            ? Redirect::to($intendedUrl)
            : Redirect::route('overview');
    }
}
