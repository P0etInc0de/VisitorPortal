@vite(['resources/js/app.js'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', __('Besucherportal')) }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-base-content antialiased">
<div class="min-h-screen" x-data="{ navOpen: false }">
    @include('layouts.navigation')

    <div class="lg:pl-72">
        @if (isset($header))
            <header class="sticky top-0 z-20 border-b border-base-300/70 bp-topbar-surface">
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
</body>
</html>
