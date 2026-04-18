@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    $customBackgroundJpg = public_path('images/monitor-background.jpg');
    $customBackgroundPng = public_path('images/monitor-background.png');

    $backgroundAsset = null;
    $hasCustomBackground = false;

    if (file_exists($customBackgroundJpg)) {
        $backgroundAsset = asset('images/monitor-background.jpg');
        $hasCustomBackground = true;
    } elseif (file_exists($customBackgroundPng)) {
        $backgroundAsset = asset('images/monitor-background.png');
        $hasCustomBackground = true;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Live-Anzeige') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--bp-bg)] text-base-content antialiased">
    <main class="bp-monitor-display {{ $hasCustomBackground ? 'bp-monitor-display-with-image' : 'bp-monitor-display-default' }}"
        @if ($backgroundAsset)
            style="background-image: linear-gradient(135deg, color-mix(in srgb, var(--color-base-200) 78%, transparent), color-mix(in srgb, var(--color-base-100) 62%, transparent)), url('{{ $backgroundAsset }}');"
        @endif
    >
        <div class="bp-monitor-overlay"></div>

        <div class="relative z-10 mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-10 py-10 sm:px-14 xl:px-18">
            <div class="flex items-start justify-between gap-8">
                <div class="flex items-center gap-6">
                    @if ($hasLogo)
                        <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" class="max-h-18 w-auto object-contain">
                    @endif

                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.28em] text-base-content/50">{{ __('Besucherportal') }}</p>
                    </div>
                </div>

                <div class="bp-monitor-chip">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
                </div>
            </div>

            <div class="mt-14 grid flex-1 gap-10 xl:grid-cols-[minmax(0,1.12fr)_28rem] xl:items-start">
                <section class="flex flex-col justify-between gap-10">
                    <div class="max-w-4xl">
                        <h1 class="bp-monitor-title">{{ __('Herzlich willkommen') }}</h1>
                        <p class="bp-monitor-subtitle">{{ __('Wir freuen uns auf Ihren Besuch. Bitte melden Sie sich bei Ihrer Ankunft am Empfang oder nutzen Sie den bereitgestellten Self-Check-in.') }}</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        @forelse ($monitorVisitors as $visitor)
                            <article class="bp-monitor-visit-card">
                                <div class="bp-monitor-visit-time">{{ $visitor['time'] }}</div>
                                <div>
                                    <h2 class="bp-monitor-visit-name">{{ $visitor['name'] }}</h2>
                                    <p class="bp-monitor-visit-company">{{ $visitor['company'] }}</p>
                                    <p class="mt-2 text-sm text-base-content/58">{{ $visitor['title'] }}</p>
                                </div>
                            </article>
                        @empty
                            <article class="bp-monitor-visit-card">
                                <div>
                                    <h2 class="bp-monitor-visit-name">{{ __('Heute sind noch keine Besuche geplant.') }}</h2>
                                </div>
                            </article>
                        @endforelse
                    </div>
                </section>

                <aside class="bp-monitor-side-panel">
                    <div>
                        <div class="bp-monitor-side-label">{{ __('Als Nächstes') }}</div>
                        <div class="mt-4 bp-monitor-chip inline-flex">{{ $monitorVisitors[0]['time'] ?? '–' }}</div>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-4xl font-semibold tracking-tight">{{ $monitorVisitors[0]['name'] ?? __('Kein Besuch geplant') }}</h2>
                        <p class="mt-3 text-2xl text-base-content/72">{{ $monitorVisitors[0]['company'] ?? __('—') }}</p>
                    </div>

                    <div class="mt-10 rounded-3xl border border-base-100/40 bg-base-100/55 px-6 py-5 backdrop-blur">
                        <div class="bp-monitor-side-label">{{ __('Nächster Besuch') }}</div>
                        <div class="mt-3 text-lg text-base-content/78">{{ __('Bitte bei Ankunft am Empfang melden oder den Self-Check-in nutzen.') }}</div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</body>
</html>
