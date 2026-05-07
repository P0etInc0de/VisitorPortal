<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Monitor;
use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $locale = UserPreferences::normalizeLocale($user?->locale)
            ?? UserPreferences::normalizeLocale($request->cookie(UserPreferences::LOCALE_COOKIE))
            ?? UserPreferences::browserLocale($request);
        $themePreference = UserPreferences::normalizeTheme($user?->theme_preference)
            ?? UserPreferences::normalizeTheme($request->cookie(UserPreferences::THEME_COOKIE))
            ?? UserPreferences::THEME_SYSTEM;

        $request->session()->put('locale', $locale);
        Cookie::queue(UserPreferences::LOCALE_COOKIE, $locale, 60 * 24 * 365);
        Cookie::queue(UserPreferences::THEME_COOKIE, $themePreference, 60 * 24 * 365);

        return redirect()->intended($this->redirectRoles($user));
    }

    /**
     * Redirect roles to different pages
     */
    public function redirectRoles(User $user): string
    {
        return match (true) {
            $user->hasRole('welcome monitor') => route('monitors.show', Monitor::query()->first(), absolute: false),
            default => route('overview', absolute: false)
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
