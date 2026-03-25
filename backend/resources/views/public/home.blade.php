<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Besucherportal</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 text-base-content">
<div class="navbar border-b border-base-300 bg-base-100">
    <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4">
        <a href="{{ route('home') }}" class="text-lg font-semibold">
            Besucherportal
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                Login
            </a>
        </div>
    </div>
</div>

<main class="mx-auto max-w-7xl px-4 py-12">
    <section class="hero rounded-2xl border border-base-300 bg-base-100 shadow-sm">
        <div class="hero-content py-16 text-center">
            <div class="max-w-3xl">
                <p class="mb-3 text-sm font-medium uppercase tracking-[0.2em] text-base-content/60">
                    ACD Besucherportal
                </p>

                <h1 class="text-4xl font-semibold leading-tight sm:text-5xl">
                    Planung, Empfang und Check-in in einem System
                </h1>

                <p class="mt-6 text-base leading-7 text-base-content/70">
                    Das Besucherportal unterstützt Mitarbeitende, Rezeption und Gäste
                    bei der strukturierten Organisation und Abwicklung von Firmenbesuchen.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Zum Login
                    </a>

                    <a href="#" class="btn btn-outline">
                        Hilfe / Support
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-3">
        {{-- Short feature overview --}}
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-lg">Für Mitarbeitende</h2>
                <p class="text-sm leading-6 text-base-content/70">
                    Besuche erfassen, verwalten und den Status jederzeit im Blick behalten.
                </p>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-lg">Für die Rezeption</h2>
                <p class="text-sm leading-6 text-base-content/70">
                    Schneller Zugriff auf anstehende Besuche sowie Check-in und Check-out.
                </p>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-lg">Für Gäste</h2>
                <p class="text-sm leading-6 text-base-content/70">
                    Empfangsunterstützung, Willkommensmonitor und optionaler Self-Check-in.
                </p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
