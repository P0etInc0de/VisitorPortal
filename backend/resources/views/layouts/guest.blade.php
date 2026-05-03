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

            const effectiveTheme = ['light', 'dark', 'true-black'].includes(preference)
                ? preference
                : (systemDark ? 'dark' : 'light');

            root.setAttribute('data-theme', effectiveTheme);
            root.classList.toggle('dark', effectiveTheme === 'dark' || effectiveTheme === 'true-black');
            root.style.colorScheme = effectiveTheme === 'light' ? 'light' : 'dark';
        })();
    </script>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(29,78,216,0.12),transparent_20rem),radial-gradient(circle_at_bottom_right,rgba(15,23,42,0.08),transparent_18rem)]"></div>

    <div class="w-full max-w-xl">
        {{ $slot }}
    </div>
</div>
</body>
</html>
