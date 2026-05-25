@php
    $preloadBackground = $monitor->firstDisplayBackgroundAssetUrl();
    $monitorLogoPath = config('branding.logo_light');
    $hasMonitorLogo = is_string($monitorLogoPath) && $monitorLogoPath !== '' && file_exists(public_path($monitorLogoPath));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" data-theme-preference="light" style="background: #eef2f8; color-scheme: light;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Live-Anzeige') }}</title>
    @includeIf('partials.favicons')
    <style>
        html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; background: #eef2f8; }
        [x-cloak] { display: none !important; }
    </style>
    @if ($preloadBackground)
        <link rel="preload" as="image" href="{{ $preloadBackground }}" fetchpriority="high">
    @endif
    @if ($hasMonitorLogo)
        <link rel="preload" as="image" href="{{ asset($monitorLogoPath) }}" fetchpriority="high">
    @endif

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (config('visitorportal.easter_egg.enabled') && (! app()->environment('production') || config('visitorportal.easter_egg.show_in_production')))
        @vite('resources/js/easter-egg.ts')
    @endif
</head>
<body class="h-screen w-screen overflow-hidden" style="margin: 0; background: #eef2f8;">
    <livewire:monitor.monitor-slides :monitor="$monitor"/>

    @livewireScripts
</body>
</html>
