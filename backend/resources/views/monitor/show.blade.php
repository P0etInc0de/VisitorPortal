@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    $visitors = [
        ['time' => '09:30', 'name' => 'Max Mustermann', 'company' => 'Beispiel GmbH'],
        ['time' => '11:00', 'name' => 'Anna Becker', 'company' => 'ACME AG'],
        ['time' => '13:30', 'name' => 'David Keller', 'company' => 'Tech Solutions'],
    ];
@endphp

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Willkommensmonitor') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-100 text-base-content antialiased">
<main class="mx-auto flex min-h-screen max-w-7xl flex-col px-8 py-10">
    <div class="flex items-start justify-between gap-8">
        <div class="flex items-center gap-5">
            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Logo') }}"
                    class="max-h-16 w-auto object-contain"
                >
            @endif

            <div>
                <p class="text-sm font-medium uppercase tracking-[0.24em] text-base-content/50">
                    {{ __('Besucherportal') }}
                </p>
                <h1 class="mt-2 text-5xl font-semibold tracking-tight">
                    {{ __('Herzlich willkommen') }}
                </h1>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 px-5 py-4 text-right">
            <p class="text-sm text-base-content/60">{{ __('Heute') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ now()->format('d.m.Y') }}</p>
        </div>
    </div>

    <div class="mt-10 grid flex-1 gap-6 xl:grid-cols-[1.4fr_1fr]">
        <section class="rounded-[2rem] border border-base-300 bg-base-200/40 p-8">
            <div class="max-w-3xl">
                <p class="text-lg text-base-content/70">
                    {{ __('Wir freuen uns auf Ihren Besuch. Bitte melden Sie sich bei Ihrer Ankunft am Empfang oder nutzen Sie den bereitgestellten Self-Check-in.') }}
                </p>
            </div>

            <div class="mt-8 grid gap-4">
                @foreach ($visitors as $visitor)
                    <div class="rounded-[1.5rem] bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="text-sm uppercase tracking-[0.16em] text-base-content/50">
                                    {{ __('Erwarteter Besuch') }}
                                </p>
                                <h2 class="mt-2 text-3xl font-semibold tracking-tight">{{ $visitor['name'] }}</h2>
                                <p class="mt-2 text-lg text-base-content/70">{{ $visitor['company'] }}</p>
                            </div>

                            <div class="rounded-2xl bg-base-200 px-4 py-3 text-right">
                                <p class="text-sm text-base-content/60">{{ __('Geplant') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $visitor['time'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-6">
            <div class="rounded-[2rem] border border-base-300 bg-base-100 p-8 shadow-sm">
                <p class="text-sm uppercase tracking-[0.18em] text-base-content/50">
                    {{ __('Hinweis') }}
                </p>
                <p class="mt-4 text-2xl font-medium leading-10">
                    {{ __('Bitte halten Sie bei Bedarf Ihren Besuchsnamen oder den Ansprechpartner bereit.') }}
                </p>
            </div>

            <div class="rounded-[2rem] border border-base-300 bg-base-100 p-8 shadow-sm">
                <p class="text-sm uppercase tracking-[0.18em] text-base-content/50">
                    {{ __('Empfang') }}
                </p>
                <p class="mt-4 text-xl leading-9 text-base-content/80">
                    {{ __('Die Rezeption unterstützt Sie beim Check-in, bei der Besucherausgabe und bei offenen Rückfragen.') }}
                </p>
            </div>

            <div class="mt-auto rounded-[2rem] border border-base-300 bg-base-100 p-8 shadow-sm">
                <p class="text-sm uppercase tracking-[0.18em] text-base-content/50">
                    {{ __('Freundlich. Klar. Vorbereitet.') }}
                </p>
                <p class="mt-4 text-lg leading-8 text-base-content/75">
                    {{ __('Der Monitor ist als ruhige und wertige Anzeige für den Eingangsbereich gedacht und kann später um Templates, Hinweise und weitere Sprachen erweitert werden.') }}
                </p>
            </div>
        </section>
    </div>
</main>
</body>
</html>
