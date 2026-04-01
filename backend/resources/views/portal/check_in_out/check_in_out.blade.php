@php
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
                <h1 class="bp-screen-title">{{ __('Besucher Ein- oder Auschecken') }}</h1>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            class="fixed top-5 right-5 z-50 max-w-sm w-full"
        >
            <div class="bg-base-100 border-l-4 border-success shadow-xl rounded-xl p-4 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>

                <div class="text-sm">
                    <p class="font-semibold">{{ __('Erfolgreich!') }}</p>
                    <p class="text-base-content/70">{{ session('success') }}</p>
                </div>

                <button @click="show = false" class="ml-auto text-base-content/40">✕</button>
            </div>
        </div>
    @endif

    @if(session('error')||$errors->any())
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            class="fixed top-5 right-5 z-50 max-w-sm w-full"
        >
            <div class="bg-base-100 border-l-4 border-error shadow-xl rounded-xl p-4 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>

                <div class="text-sm">
                    <p class="font-semibold">{{ __('Woops!') }}</p>
                    <p class="text-base-content/70">{{ __('Etwas ist schiefgelaufen') }}</p>
                </div>

                <button @click="show = false" class="ml-auto text-base-content/40">✕</button>
            </div>
        </div>
    @endif

    <div class="bp-content">
        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div>
                        <form method="post" action="{{route('portal.check_in_out.store')}}">
                            @csrf
                            <div class="grid gap-5 md:grid-cols-2">

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Gastname') }}
                                        </span>
                                    </div>
                                    <input type="text" name="name" placeholder="Name" class="input input-bordered w-full rounded-2xl" required>
                                </label>

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Email') }}
                                        </span>
                                    </div>
                                    <input type="text" name="email" placeholder="Beispiel@gmail.com" class="input input-bordered w-full rounded-2xl" required>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="submit" name="action" value="check_out" class="btn btn-outline rounded-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Check-Out') }}
                                </button>

                                <button type="submit" name="action" value="check_in" class="btn btn-primary rounded-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Check-In') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
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
