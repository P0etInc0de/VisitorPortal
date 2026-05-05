<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Support\UserPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $this->authorize('view', $request->user());

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->authorize('update', $request->user());

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        $locale = UserPreferences::normalizeLocale($request->input('locale'))
            ?? UserPreferences::normalizeLocale($request->user()->locale)
            ?? UserPreferences::browserLocale($request);
        $themePreference = UserPreferences::normalizeTheme($request->input('theme_preference'))
            ?? UserPreferences::normalizeTheme($request->user()->theme_preference)
            ?? UserPreferences::THEME_SYSTEM;

        $request->session()->put('locale', $locale);
        Cookie::queue(UserPreferences::LOCALE_COOKIE, $locale, 60 * 24 * 365);
        Cookie::queue(UserPreferences::THEME_COOKIE, $themePreference, 60 * 24 * 365);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403);
    }
}
