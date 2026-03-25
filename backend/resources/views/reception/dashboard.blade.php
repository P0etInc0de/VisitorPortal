<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Rezeption
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Schneller Überblick über heutige Besuche und Check-in
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body gap-4">
                    <h3 class="card-title">Besuch suchen</h3>

                    {{-- Search is UI only for now --}}
                    <div class="flex flex-col gap-3 md:flex-row">
                        <input
                            type="text"
                            placeholder="Name, Firma oder Ansprechpartner"
                            class="input input-bordered w-full"
                        >
                        <button class="btn btn-primary">
                            Suchen
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Heute erwartet</p>
                        <p class="text-3xl font-semibold">18</p>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Eingecheckt</p>
                        <p class="text-3xl font-semibold">9</p>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <p class="text-sm text-base-content/70">Offen</p>
                        <p class="text-3xl font-semibold">9</p>
                    </div>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="card-title">Heutige Besuche</h3>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('monitor.live') }}" class="btn btn-outline btn-sm">
                                Monitor anzeigen
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">
                                Portal
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-lg">
                            <thead>
                            <tr>
                                <th>Zeit</th>
                                <th>Gast</th>
                                <th>Firma</th>
                                <th>Host</th>
                                <th>Status</th>
                                <th class="text-right">Aktion</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>09:30</td>
                                <td>Max Mustermann</td>
                                <td>Beispiel GmbH</td>
                                <td>Jonathan Läpple</td>
                                <td>
                                    <span class="badge badge-warning">Geplant</span>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-primary btn-sm">Check-in</button>
                                </td>
                            </tr>
                            <tr>
                                <td>11:00</td>
                                <td>Anna Becker</td>
                                <td>ACME AG</td>
                                <td>Peter Beispiel</td>
                                <td>
                                    <span class="badge badge-success">Eingecheckt</span>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-outline btn-sm">Check-out</button>
                                </td>
                            </tr>
                            <tr>
                                <td>13:30</td>
                                <td>David Keller</td>
                                <td>Tech Solutions</td>
                                <td>Lena Beispiel</td>
                                <td>
                                    <span class="badge badge-warning">Geplant</span>
                                </td>
                                <td class="text-right">
                                    <button class="btn btn-primary btn-sm">Check-in</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title">Hinweis</h3>
                    <p class="text-sm leading-6 text-base-content/70">
                        Diese Ansicht ist zunächst als klickbarer Dummy gedacht.
                        Suche, Check-in, Check-out und Druckfunktionen folgen später.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
