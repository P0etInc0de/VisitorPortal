@php
    $stats = [
        [
            'label' => __('Meine Besuche heute'),
            'value' => '4',
            'meta' => __('1 Termin startet in der nächsten Stunde.'),
        ],
        [
            'label' => __('Nächster Termin'),
            'value' => '09:30',
            'meta' => __('Max Mustermann · Beispiel GmbH'),
        ],
        [
            'label' => __('Diese Woche'),
            'value' => '7',
            'meta' => __('7 geplant · 2 Entwürfe'),
        ],
    ];

    $visits = [
        [
            'time' => '09:30',
            'date' => '30.03.',
            'title' => __('Max Mustermann'),
            'company' => __('Beispiel GmbH'),
            'note' => __('Badge vorbereitet · Monitor aktiv'),
            'status' => __('Geplant'),
            'statusClass' => 'badge-warning badge-outline',
        ],
        [
            'time' => '11:00',
            'date' => '30.03.',
            'title' => __('Anna Becker'),
            'company' => __('ACME AG'),
            'note' => __('Ankunft erfolgt · Aufenthalt läuft'),
            'status' => __('Eingecheckt'),
            'statusClass' => 'badge-success badge-outline',
        ],
        [
            'time' => '14:00',
            'date' => '30.03.',
            'title' => __('David Keller'),
            'company' => __('Tech Solutions'),
            'note' => __('Badge offen · Monitorfreigabe noch ausstehend'),
            'status' => __('Entwurf'),
            'statusClass' => 'badge-ghost',
        ],
    ];

    $notes = [
        __('Besuch für Sven Maurer noch ohne finalen Zeitraum'),
        __('Gruppenbesuch Technik benötigt noch Empfangsbriefing'),
        __('Badge-Ausgabe für David Keller noch nicht bestätigt'),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Übersicht') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content">
        <section class="bp-stat-grid">
            @foreach ($stats as $stat)
                <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-sm font-medium text-base-content/65">
                            {{ $stat['label'] }}
                        </div>

                        <div class="mt-2 text-4xl font-semibold leading-none tracking-tight text-base-content">
                            {{ $stat['value'] }}
                        </div>

                        <div class="mt-2 text-sm text-base-content/70">
                            {{ $stat['meta'] }}
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="bp-main-grid">
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Anstehende Besuche') }}
                        </h2>

                        <a
                            href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.create') ? route('portal.visits.create') : url('/portal/visits/create') }}"
                            class="btn btn-outline btn-sm rounded-xl"
                        >
                            {{ __('Neuen Besuch anlegen') }}
                        </a>
                    </div>

                    <div class="space-y-4">
                        @foreach ($visits as $visit)
                            <article class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex gap-4">
                                        <div class="min-w-[72px] shrink-0">
                                            <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">
                                                {{ $visit['time'] }}
                                            </div>
                                            <div class="mt-1 text-sm text-base-content/60">
                                                {{ $visit['date'] }}
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="text-xl font-semibold tracking-tight text-base-content">
                                                {{ $visit['title'] }}
                                            </div>

                                            <div class="mt-1 text-lg text-base-content/75">
                                                {{ $visit['company'] }}
                                            </div>

                                            <div class="mt-3 text-base leading-6 text-base-content/80">
                                                {{ $visit['note'] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                        <span class="badge {{ $visit['statusClass'] }} rounded-full px-3 py-3 text-sm font-semibold">
                                            {{ $visit['status'] }}
                                        </span>

                                        <a
                                            href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.show') ? route('portal.visits.show', 1) : url('/portal/visits/1') }}"
                                            class="btn btn-outline btn-sm rounded-xl"
                                        >
                                            {{ __('Details öffnen') }}
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </article>

            <aside class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Notizen') }}
                    </h2>

                    <div class="space-y-3">
                        @foreach ($notes as $note)
                            <div class="rounded-[1.35rem] border border-base-300 bg-base-100 px-4 py-4 text-base leading-7 text-base-content/85">
                                {{ $note }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
