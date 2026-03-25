@php
    $queue = [
        ['time' => '09:30', 'guest' => 'Max Mustermann', 'company' => 'Beispiel GmbH', 'host' => 'Jonathan Läpple', 'status' => __('Geplant'), 'badge' => 'badge-warning'],
        ['time' => '11:00', 'guest' => 'Anna Becker', 'company' => 'ACME AG', 'host' => 'Lena Beispiel', 'status' => __('Eingecheckt'), 'badge' => 'badge-success'],
        ['time' => '13:30', 'guest' => 'David Keller', 'company' => 'Tech Solutions', 'host' => 'Peter Beispiel', 'status' => __('Geplant'), 'badge' => 'badge-warning'],
        ['time' => '15:00', 'guest' => 'Sven Maurer', 'company' => 'Nordwerk', 'host' => 'Jonathan Läpple', 'status' => __('Ausstehend'), 'badge' => 'badge-neutral'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ __('Rezeption') }}
            </h1>
            <p class="mt-1 text-sm text-base-content/70">
                {{ __('Operative Tagesansicht für Suche, Check-in, Check-out und laufende Besucherbewegung.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body gap-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="card-title text-xl">{{ __('Besuch suchen') }}</h2>
                            <p class="text-sm text-base-content/70">
                                {{ __('Suche nach Name, Firma oder Ansprechpartner für schnellen Zugriff am Empfang.') }}
                            </p>
                        </div>

                        <div class="badge badge-ghost">{{ __('Heute') }}</div>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                        <input
                            type="text"
                            placeholder="{{ __('Name, Firma oder Ansprechpartner') }}"
                            class="input input-bordered input-lg w-full rounded-2xl"
                        >
                        <button type="button" class="btn btn-primary rounded-2xl">
                            {{ __('Suchen') }}
                        </button>
                        <button type="button" class="btn btn-outline rounded-2xl">
                            {{ __('Scanner / Kiosk') }}
                        </button>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">{{ __('Heute erwartet') }}</p>
                        <p class="text-4xl font-semibold tracking-tight">18</p>
                    </div>
                </div>
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">{{ __('Eingecheckt') }}</p>
                        <p class="text-4xl font-semibold tracking-tight">9</p>
                    </div>
                </div>
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">{{ __('Offen') }}</p>
                        <p class="text-4xl font-semibold tracking-tight">9</p>
                    </div>
                </div>
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">{{ __('Monitorstatus') }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="badge badge-success">{{ __('Aktiv') }}</span>
                            <span class="text-sm text-base-content/70">{{ __('Template Standard') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.9fr_1fr]">
                <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body gap-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Heutige Besuche') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Direkte operative Sicht mit schnellen Aktionen für Empfang und Check-in.') }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button type="button" class="btn btn-ghost btn-sm rounded-xl">{{ __('Aktualisieren') }}</button>
                                <button type="button" class="btn btn-outline btn-sm rounded-xl">{{ __('Filter') }}</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table table-lg">
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
                                @foreach ($queue as $item)
                                    <tr>
                                        <td class="whitespace-nowrap font-medium">{{ $item['time'] }}</td>
                                        <td>{{ $item['guest'] }}</td>
                                        <td>{{ $item['company'] }}</td>
                                        <td>{{ $item['host'] }}</td>
                                        <td><span class="badge {{ $item['badge'] }}">{{ $item['status'] }}</span></td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="btn btn-primary btn-sm rounded-xl">
                                                    {{ __('Check-in') }}
                                                </button>
                                                <button type="button" class="btn btn-outline btn-sm rounded-xl">
                                                    {{ __('Check-out') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Schnellaktionen') }}</h2>

                            <div class="grid gap-3">
                                <button type="button" class="btn btn-primary justify-start rounded-xl">
                                    {{ __('Spontanen Besuch erfassen') }}
                                </button>
                                <button type="button" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Badge-Druck öffnen') }}
                                </button>
                                <button type="button" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Self-Check-in prüfen') }}
                                </button>
                                <button type="button" class="btn btn-outline justify-start rounded-xl">
                                    {{ __('Host benachrichtigen') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Nächste Ankünfte') }}</h2>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-base-300 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium">09:30 · Max Mustermann</p>
                                            <p class="text-sm text-base-content/70">Beispiel GmbH</p>
                                        </div>
                                        <span class="badge badge-warning">{{ __('Geplant') }}</span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-base-300 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium">11:00 · Anna Becker</p>
                                            <p class="text-sm text-base-content/70">ACME AG</p>
                                        </div>
                                        <span class="badge badge-success">{{ __('Eingecheckt') }}</span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-base-300 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="font-medium">13:30 · David Keller</p>
                                            <p class="text-sm text-base-content/70">Tech Solutions</p>
                                        </div>
                                        <span class="badge badge-warning">{{ __('Geplant') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Betriebsstatus') }}</h2>

                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between rounded-xl border border-base-300 p-3">
                                    <span>{{ __('Monitor') }}</span>
                                    <span class="badge badge-success">{{ __('Aktiv') }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-base-300 p-3">
                                    <span>{{ __('Mail-Benachrichtigung') }}</span>
                                    <span class="badge badge-success">{{ __('Bereit') }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-base-300 p-3">
                                    <span>{{ __('Badge-Ausgabe') }}</span>
                                    <span class="badge badge-warning">{{ __('Prüfen') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
