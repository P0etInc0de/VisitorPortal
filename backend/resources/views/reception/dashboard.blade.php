@php
    $stats = [
        [
            'label' => __('Ankünfte heute'),
            'value' => '12',
            'meta' => __('davon 3 in der nächsten Stunde'),
        ],
        [
            'label' => __('Aktuell im Haus'),
            'value' => '5',
            'meta' => __('Personen aktuell im Haus'),
        ],
        [
            'label' => __('Offene Klärungen'),
            'value' => '2',
            'meta' => __('Badge-Druck und Rückfrage an Host'),
        ],
    ];

    $filters = [__('Alle'), __('Geplant'), __('Im Haus')];

    $visits = [
        [
            'time' => '09:30',
            'date' => '30.03.',
            'title' => __('Max Mustermann'),
            'company' => __('Beispiel GmbH'),
            'host' => __('Host: Jonathan Läpple'),
            'note' => __('Badge bereit · Monitor aktiv'),
            'status' => __('Geplant'),
            'statusClass' => 'badge-warning badge-outline',
            'action' => __('Check-in'),
        ],
        [
            'time' => '11:00',
            'date' => '30.03.',
            'title' => __('Anna Becker'),
            'company' => __('ACME AG'),
            'host' => __('Host: Lena Bauer'),
            'note' => __('Ankunft erfolgt · Aufenthalt läuft'),
            'status' => __('Eingecheckt'),
            'statusClass' => 'badge-success badge-outline',
            'action' => __('Check-out'),
        ],
        [
            'time' => '14:00',
            'date' => '30.03.',
            'title' => __('Technik-Rundgang'),
            'company' => __('Gruppenbesuch'),
            'host' => __('Host: Peter Hoffmann'),
            'note' => __('Badge-Satz drucken · Empfang briefen'),
            'status' => __('Prüfen'),
            'statusClass' => 'badge-error badge-outline',
            'action' => __('Details'),
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">{{ __('Dashboard') }}</h1>
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
                    <div class="mb-4 flex flex-col gap-3">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">
                                {{ __('Heutige Besuche') }}
                            </h2>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($filters as $index => $filter)
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-xl {{ $index === 0 ? 'btn-primary' : 'btn-outline' }}"
                                    >
                                        {{ $filter }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <label class="input input-bordered flex h-11 w-full items-center gap-2 rounded-2xl">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-base-content/50">
                                <circle cx="11" cy="11" r="6" />
                                <path d="M20 20l-3.5-3.5" />
                            </svg>
                            <input
                                type="search"
                                class="grow"
                                placeholder="{{ __('Nach Name, Firma oder Host suchen') }}"
                            >
                        </label>
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

                                            <div class="mt-2 text-sm text-base-content/65">
                                                {{ $visit['host'] }}
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

                                        <div class="flex flex-wrap gap-2">
                                            <a
                                                href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.show') ? route('portal.visits.show', 1) : url('/portal/visits/1') }}"
                                                class="btn btn-outline btn-sm rounded-xl"
                                            >
                                                {{ __('Details') }}
                                            </a>

                                            <button type="button" class="btn btn-primary btn-sm rounded-xl">
                                                {{ $visit['action'] }}
                                            </button>
                                        </div>
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
                        {{ __('Direkter Zugriff') }}
                    </h2>

                    <div class="space-y-3">
                        <a
                            href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.create') ? route('portal.visits.create') : url('/portal/visits/create') }}"
                            class="btn btn-primary w-full rounded-xl"
                        >
                            {{ __('Spontanen Besuch erfassen') }}
                        </a>

                        <button type="button" class="btn btn-outline w-full rounded-xl">
                            {{ __('Badge-Druck öffnen') }}
                        </button>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
