@php
    $kioskName = isset($kiosk) && $kiosk ? $kiosk->name : config('branding.name', 'VisitorPortal');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $kioskName }}</title>
    @includeIf('partials.favicons')

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="flex min-h-screen flex-col">
    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="px-6 py-3 text-center text-xs text-base-content/40">
        {{ config('branding.name', 'VisitorPortal') }}
    </footer>
</div>
@livewireScripts
</body>
</html>
