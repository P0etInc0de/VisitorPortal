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

    $monitorMainClass = $hasCustomBackground
        ? 'relative overflow-hidden bg-base-200 bg-cover bg-center bg-no-repeat'
        : 'relative overflow-hidden bg-[radial-gradient(circle_at_top_left,color-mix(in_srgb,var(--color-primary)_10%,transparent),transparent_35%),linear-gradient(180deg,var(--color-base-100)_0%,var(--color-base-200)_100%)] bg-cover bg-center bg-no-repeat';

    $monitorChipClass = 'inline-flex min-w-40 flex-col rounded-[1.75rem] border border-base-100/60 bg-base-100/70 px-[1.4rem] py-5 shadow-xl backdrop-blur';
    $monitorVisitCardClass = 'grid gap-4 rounded-[2rem] border border-base-300 bg-base-100/85 p-6 shadow-xl backdrop-blur';
    $monitorSidePanelClass = 'rounded-[2.2rem] border border-base-300 bg-base-100/85 p-8 shadow-xl backdrop-blur';
    $monitorSideLabelClass = 'text-[0.82rem] font-bold uppercase tracking-[0.2em] text-base-content/45';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Live-Anzeige') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content antialiased">
    <main class="{{ $monitorMainClass }}"
        @if ($backgroundAsset)
            style="background-image: linear-gradient(135deg, color-mix(in srgb, var(--color-base-200) 78%, transparent), color-mix(in srgb, var(--color-base-100) 62%, transparent)), url('{{ $backgroundAsset }}');"
        @endif
    >
        <div class="absolute inset-0 bg-[linear-gradient(140deg,rgba(255,255,255,0.60),rgba(243,245,249,0.78))] backdrop-blur-[2px]"></div>

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

                <div class="{{ $monitorChipClass }}">
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
                </div>
            </div>

            <div class="mt-14 grid flex-1 gap-10 xl:grid-cols-[minmax(0,1.12fr)_28rem] xl:items-start">
                <section class="flex flex-col justify-between gap-10">
                    <div class="max-w-4xl">
                        <h1 class="text-[clamp(4rem,3.1rem+2.6vw,6rem)] font-bold leading-[0.95] tracking-[-0.06em]">{{ __('Herzlich willkommen') }}</h1>
                        <p class="mt-6 max-w-[52rem] text-[clamp(1.2rem,1rem+0.55vw,1.65rem)] leading-[1.65] text-base-content/80">{{ __('Wir freuen uns auf Ihren Besuch. Bitte melden Sie sich bei Ihrer Ankunft am Empfang oder nutzen Sie den bereitgestellten Self-Check-in.') }}</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        @forelse ($monitorVisitors as $visitor)
                            <article class="{{ $monitorVisitCardClass }}">
                                <div class="text-[2rem] font-bold leading-none tracking-[-0.05em]">{{ $visitor['time'] }}</div>
                                <div>
                                    <h2 class="text-[2rem] font-bold leading-[1.1] tracking-[-0.04em]">{{ $visitor['name'] }}</h2>
                                    <p class="mt-2 text-xl text-base-content/70">{{ $visitor['company'] }}</p>
                                    <p class="mt-2 text-sm text-base-content/58">{{ $visitor['title'] }}</p>
                                </div>
                            </article>
                        @empty
                            <article class="{{ $monitorVisitCardClass }}">
                                <div>
                                    <h2 class="text-[2rem] font-bold leading-[1.1] tracking-[-0.04em]">{{ __('Heute sind noch keine Besuche geplant.') }}</h2>
                                </div>
                            </article>
                        @endforelse
                    </div>
                </section>

                <aside class="{{ $monitorSidePanelClass }}">
                    <div>
                        <div class="{{ $monitorSideLabelClass }}">{{ __('Als Nächstes') }}</div>
                        <div class="mt-4 {{ $monitorChipClass }} inline-flex">{{ $monitorVisitors[0]['time'] ?? '–' }}</div>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-4xl font-semibold tracking-tight">{{ $monitorVisitors[0]['name'] ?? __('Kein Besuch geplant') }}</h2>
                        <p class="mt-3 text-2xl text-base-content/72">{{ $monitorVisitors[0]['company'] ?? __('—') }}</p>
                    </div>

                    <div class="mt-10 rounded-3xl border border-base-100/40 bg-base-100/55 px-6 py-5 backdrop-blur">
                        <div class="{{ $monitorSideLabelClass }}">{{ __('Nächster Besuch') }}</div>
                        <div class="mt-3 text-lg text-base-content/78">{{ __('Bitte bei Ankunft am Empfang melden oder den Self-Check-in nutzen.') }}</div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</body>
</html>
