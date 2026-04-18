<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', __('Besucherportal')) }}</title>

    <script>
        document.documentElement.classList.add('bp-preload');

        window.addEventListener('load', function () {
            document.documentElement.classList.remove('bp-preload');
            document.documentElement.classList.add('bp-ready');
        });
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-base-content antialiased">
    <div class="min-h-screen" x-data="{ navOpen: false }">
        @include('layouts.navigation')

        <div class="min-h-screen lg:ml-72">
            @if (isset($header))
                <header class="relative z-20 border-b border-base-300/70 bp-topbar-surface bp-page-header">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main class="bp-page">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
