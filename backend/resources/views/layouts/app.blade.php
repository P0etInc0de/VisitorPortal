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

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
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
    <script>
        document.addEventListener('livewire:navigating', () => {
            window.sidebarScrollPos = document.querySelector('#sidebar-content')?.scrollTop;
        });

        document.addEventListener('livewire:navigated', () => {
            if (window.sidebarScrollPos !== undefined) {
                const sidebar = document.querySelector('#sidebar-content');
                if (sidebar) {
                    sidebar.scrollTo({ top: window.sidebarScrollPos, behavior: 'instant' });
                }
            }
        });
    </script>
</body>
</html>
