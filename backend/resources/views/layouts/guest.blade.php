<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Besucherportal') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(29,78,216,0.12),transparent_20rem),radial-gradient(circle_at_bottom_right,rgba(15,23,42,0.08),transparent_18rem)]"></div>

    <div class="w-full max-w-xl">
        {{ $slot }}
    </div>
</div>
</body>
</html>
