<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Besucherportal') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="bp-guest-shell">
    <div class="bp-guest-backdrop"></div>

    <div class="w-full max-w-xl">
        {{ $slot }}
    </div>
</div>
</body>
</html>
