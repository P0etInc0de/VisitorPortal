@php
    $themePreference = $themePreference ?? \App\Support\UserPreferences::THEME_SYSTEM;
    $theme = $theme ?? \App\Support\UserPreferences::initialTheme($themePreference);
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $theme }}"
    data-theme-preference="{{ $themePreference }}"
>
<head>
    <script>
        (function () {
            const root = document.documentElement;
            const preference = root.dataset.themePreference || 'system';
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const systemTheme = systemDark ? 'dark' : 'light';

            const effectiveTheme = ['light', 'dark', 'true-black'].includes(preference)
                ? preference
                : systemTheme;

            const backgroundColor = effectiveTheme === 'true-black'
                ? '#000000'
                : (effectiveTheme === 'dark' ? '#09090b' : '#f3f5f9');

            root.setAttribute('data-theme', effectiveTheme);
            root.classList.toggle('dark', effectiveTheme === 'dark' || effectiveTheme === 'true-black');
            root.style.colorScheme = effectiveTheme === 'light' ? 'light' : 'dark';
            root.style.backgroundColor = backgroundColor;

            document.cookie = 'theme_effective=' + encodeURIComponent(systemTheme)
                + '; Path=/; Max-Age=31536000; SameSite=Lax';
        })();
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('branding.name', 'VisitorPortal') }}</title>
    @includeIf('partials.favicons')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('visitorportal.easter_egg.enabled') && (! app()->environment('production') || config('visitorportal.easter_egg.show_in_production')))
        @vite('resources/js/easter-egg.ts')
    @endif
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(29,78,216,0.12),transparent_20rem),radial-gradient(circle_at_bottom_right,rgba(15,23,42,0.08),transparent_18rem)]"></div>

    <div class="w-full max-w-xl">
        {{ $slot }}
    </div>
</div>
</body>
</html>
