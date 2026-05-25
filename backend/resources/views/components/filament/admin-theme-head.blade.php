@php
    $themePreference = \App\Support\UserPreferences::normalizeTheme(auth()->user()?->theme_preference)
        ?? \App\Support\UserPreferences::THEME_SYSTEM;
    $themeSyncUrl = route('profile.theme-preference.update');
    $trueBlackLabel = __('True Black (OLED)');
@endphp

@includeIf('partials.favicons')

@include('filament.partials.admin-theme-script', [
    'themePreference' => $themePreference,
    'themeSyncUrl' => $themeSyncUrl,
    'trueBlackLabel' => $trueBlackLabel,
])

@include('filament.partials.admin-theme-style')
