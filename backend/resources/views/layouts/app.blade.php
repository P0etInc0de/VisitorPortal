@php
    $themePreference = $themePreference ?? \App\Support\UserPreferences::THEME_SYSTEM;
    $theme = $theme ?? \App\Support\UserPreferences::initialTheme($themePreference);
    $themeBackground = match ($theme) {
        \App\Support\UserPreferences::THEME_TRUE_BLACK => '#000000',
        \App\Support\UserPreferences::THEME_DARK => '#09090b',
        default => '#f3f5f9',
    };
    $themeColorScheme = $theme === \App\Support\UserPreferences::THEME_LIGHT ? 'light' : 'dark';
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $theme }}"
    data-theme-preference="{{ $themePreference }}"
    style="--bp-theme-background: {{ $themeBackground }}; background-color: {{ $themeBackground }}; color-scheme: {{ $themeColorScheme }};"
>
<head>
    <style>
        [x-cloak] { display: none !important; }
        html[data-theme="light"], html[data-theme="light"] body { background-color: #f3f5f9; color-scheme: light; }
        html[data-theme="dark"], html[data-theme="dark"] body { background-color: #09090b; color-scheme: dark; }
        html[data-theme="true-black"], html[data-theme="true-black"] body { background-color: #000000; color-scheme: dark; }
    </style>
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
            root.style.setProperty('--bp-theme-background', backgroundColor);
            root.style.backgroundColor = backgroundColor;

            document.cookie = 'theme_effective=' + encodeURIComponent(systemTheme)
                + '; Path=/; Max-Age=31536000; SameSite=Lax';
        })();
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('branding.name', config('app.name', 'VisitorPortal')) }}</title>
    @includeIf('partials.favicons')
    {{ $head ?? '' }}

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('visitorportal.easter_egg.enabled') && (! app()->environment('production') || config('visitorportal.easter_egg.show_in_production')))
        @vite('resources/js/easter-egg.ts')
    @endif
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased" style="background-color: {{ $themeBackground }};">
<div class="min-h-screen" x-data="{ navOpen: false }">
    @include('layouts.navigation')

    <div class="min-w-0 lg:ml-72">
        @if (isset($header))
            <header class="relative z-20 border-b border-base-300/70 bg-base-100 [&_.btn]:min-h-11">
                <div class="w-full max-w-none px-4 py-5 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <main class="pt-6 pb-8 lg:pt-5 lg:pb-6 {{ $pageClass ?? '' }}">
            <div class="w-full max-w-none px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
