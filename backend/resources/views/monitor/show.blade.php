@php
    $preloadBackground = $monitor->firstDisplayBackgroundAssetUrl();
    $hasMonitorLogo = file_exists(public_path('images/logo-light.png'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" data-theme-preference="light" style="background: #eef2f8; color-scheme: light;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Live-Anzeige') }}</title>
    <style>
        html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; background: #eef2f8; }
        [x-cloak] { display: none !important; }
    </style>
    @if ($preloadBackground)
        <link rel="preload" as="image" href="{{ $preloadBackground }}" fetchpriority="high">
    @endif
    @if ($hasMonitorLogo)
        <link rel="preload" as="image" href="{{ asset('images/logo-light.png') }}" fetchpriority="high">
    @endif

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen w-screen overflow-hidden" style="margin: 0; background: #eef2f8;">
    <livewire:monitor.monitor-slides :monitor="$monitor"/>

    @livewireScripts
</body>
</html>
