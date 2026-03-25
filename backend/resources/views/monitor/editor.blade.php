<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ __('Willkommensmonitor Editor') }}
                </h1>
                <p class="mt-1 text-sm text-base-content/70">
                    {{ __('Anzeige, Texte und Sichtbarkeit des Willkommensmonitors vorbereiten und steuern.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('monitor.live') }}" class="btn btn-outline rounded-2xl">
                    {{ __('Live anzeigen') }}
                </a>
                <button type="button" class="btn btn-primary rounded-2xl">
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                <div class="space-y-6">
                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Inhalt und Darstellung') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Texte, Template und allgemeine Wirkung des Monitors verwalten.') }}
                                </p>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Template') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Standard') }}</option>
                                        <option>{{ __('Kompakt') }}</option>
                                        <option>{{ __('Event / Besuchergruppe') }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium">{{ __('Tonality') }}</label>
                                    <select class="select select-bordered select-lg w-full rounded-2xl">
                                        <option>{{ __('Ruhig und formell') }}</option>
                                        <option>{{ __('Freundlich und offen') }}</option>
                                        <option>{{ __('Reduziert und sachlich') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">{{ __('Hauptüberschrift') }}</label>
                                <input type="text" class="input input-bordered input-lg w-full rounded-2xl" value="{{ __('Herzlich willkommen') }}">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">{{ __('Einleitung') }}</label>
                                <textarea class="textarea textarea-bordered min-h-28 w-full rounded-2xl">{{ __('Wir freuen uns auf Ihren Besuch. Bitte melden Sie sich bei Ihrer Ankunft am Empfang oder nutzen Sie den bereitgestellten Self-Check-in.') }}</textarea>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium">{{ __('Zusätzlicher Hinweis') }}</label>
                                <textarea class="textarea textarea-bordered min-h-24 w-full rounded-2xl">{{ __('Bitte halten Sie bei Bedarf Ihren Besuchsnamen oder den Ansprechpartner bereit.') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-5">
                            <div>
                                <h2 class="card-title text-xl">{{ __('Sichtbarkeit und Verhalten') }}</h2>
                                <p class="text-sm text-base-content/70">
                                    {{ __('Steuerung der Anzeige für Eingang, Empfang und besondere Besuchssituationen.') }}
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Logo anzeigen') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Optionales PNG aus dem Projekt verwenden.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Heutige Besucher anzeigen') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Geplante Besuche in der Live-Ansicht aufnehmen.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                                    <div>
                                        <div class="font-medium">{{ __('Datum anzeigen') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Aktuellen Tageskontext sichtbar machen.') }}</div>
                                    </div>
                                </label>

                                <label class="flex gap-4 rounded-2xl border border-base-300 p-4">
                                    <input type="checkbox" class="checkbox checkbox-sm mt-1">
                                    <div>
                                        <div class="font-medium">{{ __('Mehrsprachigkeit vorbereiten') }}</div>
                                        <div class="text-sm text-base-content/70">{{ __('Späteres Umschalten von Texten vorbereiten.') }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Live-Vorschau') }}</h2>
                            <p class="text-sm text-base-content/70">
                                {{ __('Annäherung an die spätere Monitoranzeige auf Basis der aktuellen Einstellungen.') }}
                            </p>

                            <div class="mt-3 rounded-[1.5rem] border border-base-300 bg-base-200/40 p-5">
                                <p class="text-xs uppercase tracking-[0.18em] text-base-content/50">
                                    {{ __('Vorschau') }}
                                </p>
                                <h3 class="mt-3 text-2xl font-semibold">{{ __('Herzlich willkommen') }}</h3>
                                <p class="mt-3 text-sm leading-6 text-base-content/70">
                                    {{ __('Wir freuen uns auf Ihren Besuch. Bitte melden Sie sich bei Ihrer Ankunft am Empfang oder nutzen Sie den bereitgestellten Self-Check-in.') }}
                                </p>

                                <div class="mt-5 space-y-3">
                                    <div class="rounded-xl bg-base-100 p-3">
                                        <div class="font-medium">09:30 · Max Mustermann</div>
                                        <div class="text-sm text-base-content/70">Beispiel GmbH</div>
                                    </div>
                                    <div class="rounded-xl bg-base-100 p-3">
                                        <div class="font-medium">11:00 · Anna Becker</div>
                                        <div class="text-sm text-base-content/70">ACME AG</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4">
                                <a href="{{ route('monitor.live') }}" class="btn btn-outline w-full rounded-xl">
                                    {{ __('Live-Ansicht öffnen') }}
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="card rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title text-xl">{{ __('Hinweise') }}</h2>

                            <ul class="list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/70">
                                <li>{{ __('Die Anzeige soll ruhig, freundlich und wertig wirken.') }}</li>
                                <li>{{ __('Interne Steuerung bleibt getrennt von der eigentlichen Live-Ansicht.') }}</li>
                                <li>{{ __('Template- und Sprachlogik kann später technisch erweitert werden.') }}</li>
                            </ul>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
