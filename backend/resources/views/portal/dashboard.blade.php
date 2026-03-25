@php
    $stats = [
        ['label' => __('Heutige Besuche'), 'value' => '12', 'hint' => __('davon 3 in der nächsten Stunde')],
        ['label' => __('Eingecheckt'), 'value' => '5', 'hint' => __('2 Gäste aktuell im Haus')],
        ['label' => __('Offen'), 'value' => '7', 'hint' => __('inklusive geplanter Nachmittagsbesuche')],
    ];

    $visits = [
        ['time' => '09:30', 'guest' => 'Max Mustermann', 'company' => 'Beispiel GmbH', 'host' => 'Jonathan Läpple', 'status' => __('Geplant'), 'badge' => 'badge-warning'],
        ['time' => '11:00', 'guest' => 'Anna Becker', 'company' => 'ACME AG', 'host' => 'Lena Beispiel', 'status' => __('Eingecheckt'), 'badge' => 'badge-success'],
        ['time' => '14:00', 'guest' => 'David Keller', 'company' => 'Tech Solutions', 'host' => 'Peter Beispiel', 'status' => __('Entwurf'), 'badge' => 'badge-neutral'],
    ];

    $highlights = [
        ['title' => __('Rezeption'), 'text' => __('Tagesübersicht, Suche und Check-in im schnellen operativen Zugriff.'), 'route' => route('reception.dashboard'), 'action' => __('Zur Rezeption')],
        ['title' => __('Willkommensmonitor'), 'text' => __('Aktuelle Anzeige für Besucher, Empfang und Eingangsbereich prüfen.'), 'route' => route('monitor.live'), 'action' => __('Monitor öffnen')],
        ['title' => __('Profil'), 'text' => __('Eigene Kontodaten, Passwort und persönliche Einstellungen verwalten.'), 'route' => route('profile.edit'), 'action' => __('Profil öffnen')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ __('Portal-Dashboard') }}
                </h1>
                <p class="mt-1 text-sm text-base-content/70">
                    {{ __('Übersicht über anstehende Besuche, aktuellen Tagesstatus und schnelle Aktionen.') }}
                </p>
            </div>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 lg:grid-cols-3">
                @foreach ($stats as $stat)
                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-sm text-base-content/70">{{ $stat['label'] }}</p>
                            <div class="mt-2 flex items-end justify-between gap-4">
                                <p class="text-4xl font-semibold tracking-tight">{{ $stat['value'] }}</p>
                                <span class="badge badge-ghost">{{ __('Heute') }}</span>
                            </div>
                            <p class="text-sm text-base-content/60">{{ $stat['hint'] }}</p>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body gap-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Meine nächsten Besuche') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Geplante, laufende und vorbereitete Besuche im direkten Überblick.') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-ghost btn-sm rounded-xl">
                                    {{ __('Filtern') }}
                                </button>
                                <a href="{{ route('portal.visits.create') }}" class="btn btn-outline btn-sm rounded-xl">
                                    {{ __('Neu') }}
                                </a>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>{{ __('Zeit') }}</th>
                                    <th>{{ __('Gast') }}</th>
                                    <th>{{ __('Firma') }}</th>
                                    <th>{{ __('Host') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Aktion') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($visits as $visit)
                                    <tr>
                                        <td class="whitespace-nowrap font-medium">{{ $visit['time'] }}</td>
                                        <td>{{ $visit['guest'] }}</td>
                                        <td>{{ $visit['company'] }}</td>
                                        <td>{{ $visit['host'] }}</td>
                                        <td>
                                            <span class="badge {{ $visit['badge'] }}">{{ $visit['status'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('portal.visits.show') }}" class="btn btn-ghost btn-xs rounded-lg">
                                                {{ __('Details') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-200/60 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-medium">{{ __('Nächster anstehender Besuch') }}</p>
                                    <p class="text-sm text-base-content/70">
                                        {{ __('Max Mustermann, 09:30 Uhr, Beispiel GmbH, Ansprechpartner Jonathan Läpple') }}
                                    </p>
                                </div>
                                <a href="{{ route('portal.visits.show') }}" class="btn btn-primary btn-sm rounded-xl">
                                    {{ __('Besuch öffnen') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Schnellzugriff') }}</h2>

                            <div class="mt-1 grid gap-3">
                                <a href="{{ route('portal.visits.create') }}" class="btn btn-primary justify-start rounded-xl">
                                    {{ __('Neuen Besuch anlegen') }}
                                </a>
                                <a href="{{ route('reception.dashboard') }}" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Check-in in der Rezeption') }}
                                </a>
                                <a href="{{ route('monitor.editor') }}" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Monitor bearbeiten') }}
                                </a>
                                <a href="{{ route('monitor.live') }}" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Monitor anzeigen') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    @foreach ($highlights as $item)
                        <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                            <div class="card-body">
                                <h3 class="text-lg font-semibold">{{ $item['title'] }}</h3>
                                <p class="text-sm leading-6 text-base-content/70">{{ $item['text'] }}</p>
                                <div class="pt-2">
                                    <a href="{{ $item['route'] }}" class="btn btn-ghost btn-sm rounded-xl">
                                        {{ $item['action'] }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
