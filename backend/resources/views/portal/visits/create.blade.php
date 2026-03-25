<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ __('Besuch erfassen') }}
            </h1>
            <p class="mt-1 text-sm text-base-content/70">
                {{ __('Besuchsdaten, Termin, Rezeption und Monitoranzeige in einem strukturierten Ablauf vorbereiten.') }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
                <div class="space-y-6">
                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Gast und Ansprechpartner') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Grundlegende Besuchsdaten und interne Zuordnung des Termins.') }}
                                </p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Gastname') }}</label>
                                    <input type="text" class="input input-bordered input-lg w-full rounded-2xl" placeholder="Max Mustermann">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Gastfirma') }}</label>
                                    <input type="text" class="input input-bordered input-lg w-full rounded-2xl" placeholder="Beispiel GmbH">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('E-Mail des Gasts') }}</label>
                                    <input type="email" class="input input-bordered input-lg w-full rounded-2xl" placeholder="max.mustermann@example.com">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Telefon') }}</label>
                                    <input type="text" class="input input-bordered input-lg w-full rounded-2xl" placeholder="+49 170 0000000">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Ansprechpartner') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Jonathan Läpple') }}</option>
                                        <option>{{ __('Lena Beispiel') }}</option>
                                        <option>{{ __('Peter Beispiel') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Abteilung') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Vertrieb') }}</option>
                                        <option>{{ __('Entwicklung') }}</option>
                                        <option>{{ __('Empfang') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Termin und Ablauf') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Zeitfenster, Status und operative Vorbereitung für den Besuch.') }}
                                </p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Datum') }}</label>
                                    <input type="date" class="input input-bordered input-lg w-full rounded-2xl">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Von') }}</label>
                                    <input type="time" class="input input-bordered input-lg w-full rounded-2xl">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Bis') }}</label>
                                    <input type="time" class="input input-bordered input-lg w-full rounded-2xl">
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Status') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Entwurf') }}</option>
                                        <option>{{ __('Geplant') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Besuchergruppe') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Einzelbesuch') }}</option>
                                        <option>{{ __('Mehrere Gäste') }}</option>
                                        <option>{{ __('Gruppe / Terminblock') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Check-in-Modus') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Empfang') }}</option>
                                        <option>{{ __('Self-Check-in optional') }}</option>
                                        <option>{{ __('Self-Check-in bevorzugt') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">{{ __('Notizen') }}</label>
                                <textarea class="textarea textarea-bordered min-h-36 w-full rounded-2xl" placeholder="{{ __('Hinweise für Empfang, Gast oder Ansprechpartner') }}"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Kommunikation und Ausgabe') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Vorbereitung für Benachrichtigungen, Ausweis und Willkommensmonitor.') }}
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Host am Besuchstag informieren') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Erinnerung und Ankunftsbenachrichtigung vorbereiten.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Besucherausweis vorbereiten') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Badge-Ausgabe für den Empfang einplanen.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Auf Monitor anzeigen') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Besuch im Eingangsbereich sichtbar machen.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1">
                                    <div>
                                        <div class="font-medium">{{ __('Änderungen protokollieren') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Spätere Nachvollziehbarkeit vorbereiten.') }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Vorschau') }}</h2>
                            <p class="text-sm text-base-content/70">
                                {{ __('Kurze inhaltliche Zusammenfassung des aktuell erfassten Besuchs.') }}
                            </p>

                            <div class="mt-2 rounded-2xl border border-base-300 bg-base-200/50 p-4">
                                <p class="font-medium">Max Mustermann</p>
                                <p class="text-sm text-base-content/70">Beispiel GmbH</p>
                                <div class="mt-4 space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <span class="text-base-content/60">{{ __('Datum') }}</span>
                                        <span>28.03.2026</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-base-content/60">{{ __('Zeit') }}</span>
                                        <span>09:30 - 11:00</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-base-content/60">{{ __('Host') }}</span>
                                        <span>{{ __('Jonathan Läpple') }}</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span class="text-base-content/60">{{ __('Status') }}</span>
                                        <span>{{ __('Geplant') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Nächste Schritte') }}</h2>

                            <ul class="list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/70">
                                <li>{{ __('Benachrichtigung an Ansprechpartner vorbereiten') }}</li>
                                <li>{{ __('Badge-Ausgabe im Empfang verfügbar machen') }}</li>
                                <li>{{ __('Willkommensmonitor bei Bedarf aktivieren') }}</li>
                            </ul>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Aktionen') }}</h2>

                            <div class="grid gap-3">
                                <button type="button" class="btn btn-primary rounded-xl">
                                    {{ __('Speichern') }}
                                </button>
                                <button type="button" class="btn btn-outline rounded-xl">
                                    {{ __('Als Entwurf sichern') }}
                                </button>
                                <button type="button" class="btn btn-ghost rounded-xl">
                                    {{ __('Vorschau öffnen') }}
                                </button>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
