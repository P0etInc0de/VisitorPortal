<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', __('Besucherportal')) }}</title>


    <script>
        document.documentElement.classList.add('is-preload');

        window.addEventListener('load', function () {
            document.documentElement.classList.remove('is-preload');
            document.documentElement.classList.add('is-ready');
        });
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="min-h-screen" x-data="{ navOpen: false }">
    @include('layouts.navigation')

    <div class="min-w-0 xl:ml-72">
        @if (isset($header))
            <header class="relative z-20 border-b border-base-300/70 bg-base-100 [&_.btn]:min-h-11">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <main class="pt-6 pb-8 lg:pt-5 lg:pb-6 {{ $pageClass }}">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
