<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Portal-Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Überblick über eigene Besuche und schnelle Aktionen
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('portal.visits.create') }}" class="btn btn-primary btn-sm">
                    Besuch erfassen
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-3">
                {{-- Simple dummy stats --}}
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Heutige Besuche</p>
                        <p class="text-3xl font-semibold">12</p>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Davon eingecheckt</p>
                        <p class="text-3xl font-semibold">5</p>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Offene Termine</p>
                        <p class="text-3xl font-semibold">7</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Meine nächsten Besuche</h3>
                            <a href="{{ route('portal.visits.create') }}" class="btn btn-outline btn-sm">
                                Neu
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Zeit</th>
                                    <th>Gast</th>
                                    <th>Firma</th>
                                    <th>Status</th>
                                    <th class="text-right">Aktion</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>09:30</td>
                                    <td>Max Mustermann</td>
                                    <td>Beispiel GmbH</td>
                                    <td>
                                        <span class="badge badge-warning">Geplant</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('portal.visits.show') }}" class="btn btn-ghost btn-xs">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>11:00</td>
                                    <td>Anna Becker</td>
                                    <td>ACME AG</td>
                                    <td>
                                        <span class="badge badge-success">Eingecheckt</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('portal.visits.show') }}" class="btn btn-ghost btn-xs">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>14:00</td>
                                    <td>David Keller</td>
                                    <td>Tech Solutions</td>
                                    <td>
                                        <span class="badge badge-neutral">Entwurf</span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('portal.visits.show') }}" class="btn btn-ghost btn-xs">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">Schnellzugriff</h3>

                            <div class="mt-2 flex flex-col gap-2">
                                <a href="{{ route('portal.visits.create') }}" class="btn btn-primary btn-sm justify-start">
                                    Neuen Besuch anlegen
                                </a>

                                <a href="{{ route('reception.dashboard') }}" class="btn btn-outline btn-sm justify-start">
                                    Zur Rezeption
                                </a>

                                <a href="{{ route('monitor.live') }}" class="btn btn-outline btn-sm justify-start">
                                    Willkommensmonitor
                                </a>

                                <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm justify-start">
                                    Profil bearbeiten
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title">Hinweise</h3>
                            <ul class="list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/70">
                                <li>Dummy-Daten und Statuswerte dienen aktuell nur der UI-Darstellung.</li>
                                <li>Spätere Filter, Suche und Verlauf können darauf aufbauen.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
