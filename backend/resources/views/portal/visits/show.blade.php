@php
    $history = [
        __('27.03.2026, 15:20 Uhr: Besuch angelegt'),
        __('27.03.2026, 15:35 Uhr: Termin angepasst'),
        __('27.03.2026, 15:40 Uhr: Monitoranzeige aktiviert'),
    ];

    $guests = [
        ['name' => 'Max Mustermann', 'company' => 'Beispiel GmbH'],
        ['name' => 'Begleitperson', 'company' => __('Optionaler zweiter Gast')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ __('Besuchsdetail') }}
                </h1>
                <span class="badge badge-warning">{{ __('Geplant') }}</span>
            </div>
            <p class="mt-1 text-sm text-base-content/70">
                {{ __('Termin, Status, Gäste und operative Informationen im Überblick.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-6 xl:grid-cols-[1.8fr_1fr]">
                <div class="space-y-6">
                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-sm text-base-content/60">{{ __('Gast') }}</p>
                                    <h2 class="text-3xl font-semibold tracking-tight">Max Mustermann</h2>
                                    <p class="mt-2 text-base-content/70">Beispiel GmbH</p>
                                </div>

                                <div class="grid gap-2 text-sm text-base-content/70">
                                    <div class="rounded-xl bg-base-200 px-4 py-3">
                                        <span class="font-medium text-base-content">{{ __('Check-in') }}:</span>
                                        {{ __('noch nicht erfolgt') }}
                                    </div>
                                    <div class="rounded-xl bg-base-200 px-4 py-3">
                                        <span class="font-medium text-base-content">{{ __('Check-out') }}:</span>
                                        {{ __('noch nicht erfolgt') }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Gastfirma') }}</div>
                                    <div class="mt-1">Beispiel GmbH</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('E-Mail') }}</div>
                                    <div class="mt-1">max.mustermann@example.com</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Telefon') }}</div>
                                    <div class="mt-1">+49 170 0000000</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Ansprechpartner') }}</div>
                                    <div class="mt-1">{{ __('Jonathan Läpple') }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Abteilung') }}</div>
                                    <div class="mt-1">{{ __('Vertrieb') }}</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Check-in-Modus') }}</div>
                                    <div class="mt-1">{{ __('Self-Check-in optional') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <h2 class="card-title text-xl">{{ __('Termin und Ablauf') }}</h2>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Datum') }}</div>
                                    <div class="mt-1">28.03.2026</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Von') }}</div>
                                    <div class="mt-1">09:30</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Bis') }}</div>
                                    <div class="mt-1">11:00</div>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-base-content/60">{{ __('Status') }}</div>
                                    <div class="mt-1">{{ __('Geplant') }}</div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-base-300 bg-base-200/50 p-4">
                                <p class="font-medium">{{ __('Notizen') }}</p>
                                <p class="mt-2 leading-7 text-base-content/80">
                                    {{ __('Gast am Haupteingang empfangen. Besucherausweis soll vorbereitet sein. Der Ansprechpartner wird am Besuchstag zusätzlich per E-Mail informiert.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <h2 class="card-title text-xl">{{ __('Besucherliste') }}</h2>

                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($guests as $guest)
                                    <div class="rounded-2xl border border-base-300 p-4">
                                        <div class="font-medium">{{ $guest['name'] }}</div>
                                        <div class="text-sm text-base-content/70">{{ $guest['company'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Aktionen') }}</h2>

                            <div class="grid gap-3">
                                <button type="button" class="btn btn-primary rounded-xl">
                                    {{ __('Check-in vorbereiten') }}
                                </button>
                                <button type="button" class="btn btn-outline rounded-xl">
                                    {{ __('Benachrichtigung auslösen') }}
                                </button>
                                <button type="button" class="btn btn-outline rounded-xl">
                                    {{ __('Badge anzeigen') }}
                                </button>
                                <button type="button" class="btn btn-outline rounded-xl">
                                    {{ __('Auf Monitor anzeigen') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Kommunikation') }}</h2>

                            <div class="space-y-3 text-sm">
                                <div class="rounded-xl border border-base-300 p-4">
                                    <div class="font-medium">{{ __('Host-Benachrichtigung') }}</div>
                                    <div class="mt-1 text-base-content/70">{{ __('Aktiviert') }}</div>
                                </div>
                                <div class="rounded-xl border border-base-300 p-4">
                                    <div class="font-medium">{{ __('Monitoranzeige') }}</div>
                                    <div class="mt-1 text-base-content/70">{{ __('Aktiviert') }}</div>
                                </div>
                                <div class="rounded-xl border border-base-300 p-4">
                                    <div class="font-medium">{{ __('Badge-Ausgabe') }}</div>
                                    <div class="mt-1 text-base-content/70">{{ __('Vorbereitet') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Verlauf') }}</h2>

                            <ul class="space-y-3 text-sm leading-6 text-base-content/70">
                                @foreach ($history as $entry)
                                    <li class="rounded-xl border border-base-300 p-3">{{ $entry }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
