<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">{{ __('Dashboard') }}</h1>
            </div>

            <div class="bp-screen-actions">
                <a
                    href="{{ route('monitor.editor') }}"
                    class="btn btn-outline btn-sm rounded-xl"
                >
                    {{ __('Editor öffnen') }}
                </a>

                <a
                    href="{{ route('portal.visits.create') }}"
                    class="btn btn-primary btn-sm rounded-xl"
                >
                    {{ __('Spontanen Besuch erfassen') }}
                </a>
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
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Heutige Termine') }}
                        </h2>

                        <a
                            href="{{ route('reception.all-visits') }}"
                            class="btn btn-outline btn-sm rounded-xl"
                        >
                            {{ __('Alle Besuche') }}
                        </a>
                    </div>

                    @if ($visits->isEmpty())
                        <div class="rounded-[1.5rem] border border-dashed border-base-300 bg-base-100 px-5 py-10 text-center text-base-content/65">
                            {{ __('Für heute sind aktuell keine Termine geplant.') }}
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($visits as $visit)
                                <article x-data="{ selectedParticipant: null }" class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <h3 class="text-xl font-semibold tracking-tight text-base-content">
                                                        {{ $visit['title'] }}
                                                    </h3>

                                                    <span class="badge badge-ghost rounded-full px-3 py-3 text-sm">
                                                        {{ $visit['time'] }} · {{ $visit['date'] }}
                                                    </span>
                                                </div>

                                                <div class="mt-2 text-sm text-base-content/65">
                                                    {{ __('Host') }}: {{ $visit['host'] }}
                                                </div>

                                                @if (!empty($visit['notes']))
                                                    <div class="mt-2 text-sm text-base-content/70">
                                                        {{ $visit['notes'] }}
                                                    </div>
                                                @endif
                                            </div>

                                            <a
                                                href="{{ route('portal.visits.show', $visit['id']) }}"
                                                class="btn btn-outline btn-sm rounded-xl"
                                            >
                                                {{ __('Termin öffnen') }}
                                            </a>
                                        </div>

                                        <div class="space-y-2">
                                            @foreach ($visit['visible_participants'] as $participant)
                                                <div class="rounded-2xl border border-base-300 bg-base-100">
                                                    <button
                                                        type="button"
                                                        class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left"
                                                        @click="selectedParticipant = selectedParticipant === '{{ $participant['row_key'] }}' ? null : '{{ $participant['row_key'] }}'"
                                                    >
                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                                <span class="badge {{ $participant['status_class'] }} rounded-full px-3 py-3 text-xs font-semibold">
                                                                    {{ $participant['status_label'] }}
                                                                </span>
                                                            </div>

                                                            <div class="mt-1 text-sm text-base-content/65">
                                                                {{ $participant['company'] ?: __('Keine Firma hinterlegt') }}
                                                            </div>
                                                        </div>

                                                        <div class="text-xs text-base-content/50">
                                                            {{ __('Aktionen') }}
                                                        </div>
                                                    </button>

                                                    <div
                                                        x-cloak
                                                        x-show="selectedParticipant === '{{ $participant['row_key'] }}'"
                                                        class="border-t border-base-300 px-4 pb-4 pt-3"
                                                    >
                                                        <div class="flex flex-wrap gap-2">
                                                            @if ($participant['can_print_badge'])
                                                                <form method="POST" action="{{ route('reception.participants.badge', [$visit['id'], $participant['visitor_id']]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-outline btn-sm rounded-xl">
                                                                        {{ __('Badge drucken') }}
                                                                    </button>
                                                                </form>
                                                            @endif

                                                            @if ($participant['can_check_in'])
                                                                <form method="POST" action="{{ route('reception.participants.check-in', [$visit['id'], $participant['visitor_id']]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-primary btn-sm rounded-xl">
                                                                        {{ __('Check-in') }}
                                                                    </button>
                                                                </form>
                                                            @endif

                                                            @if ($participant['can_check_out'])
                                                                <form method="POST" action="{{ route('reception.participants.check-out', [$visit['id'], $participant['visitor_id']]) }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-primary btn-sm rounded-xl">
                                                                        {{ __('Check-out') }}
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if ($visit['hidden_count'] > 0)
                                                <details class="rounded-2xl border border-base-300 bg-base-100">
                                                    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-base-content/75">
                                                        + {{ $visit['hidden_count'] }} {{ __('weitere Teilnehmende') }}
                                                    </summary>

                                                    <div class="space-y-2 border-t border-base-300 p-3">
                                                        @foreach ($visit['hidden_participants'] as $participant)
                                                            <div class="rounded-2xl border border-base-300 bg-base-100">
                                                                <button
                                                                    type="button"
                                                                    class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left"
                                                                    @click="selectedParticipant = selectedParticipant === '{{ $participant['row_key'] }}' ? null : '{{ $participant['row_key'] }}'"
                                                                >
                                                                    <div class="min-w-0">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                                            <span class="badge {{ $participant['status_class'] }} rounded-full px-3 py-3 text-xs font-semibold">
                                                                                {{ $participant['status_label'] }}
                                                                            </span>
                                                                        </div>

                                                                        <div class="mt-1 text-sm text-base-content/65">
                                                                            {{ $participant['company'] ?: __('Keine Firma hinterlegt') }}
                                                                        </div>
                                                                    </div>

                                                                    <div class="text-xs text-base-content/50">
                                                                        {{ __('Aktionen') }}
                                                                    </div>
                                                                </button>

                                                                <div
                                                                    x-cloak
                                                                    x-show="selectedParticipant === '{{ $participant['row_key'] }}'"
                                                                    class="border-t border-base-300 px-4 pb-4 pt-3"
                                                                >
                                                                    <div class="flex flex-wrap gap-2">
                                                                        @if ($participant['can_print_badge'])
                                                                            <form method="POST" action="{{ route('reception.participants.badge', [$visit['id'], $participant['visitor_id']]) }}">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-outline btn-sm rounded-xl">
                                                                                    {{ __('Badge drucken') }}
                                                                                </button>
                                                                            </form>
                                                                        @endif

                                                                        @if ($participant['can_check_in'])
                                                                            <form method="POST" action="{{ route('reception.participants.check-in', [$visit['id'], $participant['visitor_id']]) }}">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-primary btn-sm rounded-xl">
                                                                                    {{ __('Check-in') }}
                                                                                </button>
                                                                            </form>
                                                                        @endif

                                                                        @if ($participant['can_check_out'])
                                                                            <form method="POST" action="{{ route('reception.participants.check-out', [$visit['id'], $participant['visitor_id']]) }}">
                                                                                @csrf
                                                                                <button type="submit" class="btn btn-primary btn-sm rounded-xl">
                                                                                    {{ __('Check-out') }}
                                                                                </button>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>

            <aside class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Direkter Zugriff') }}
                    </h2>

                    <div class="space-y-3">
                        <a
                            href="{{ route('portal.visits.create') }}"
                            class="btn btn-primary w-full rounded-xl"
                        >
                            {{ __('Spontanen Besuch erfassen') }}
                        </a>

                        <a
                            href="{{ route('reception.all-visits') }}"
                            class="btn btn-outline w-full rounded-xl"
                        >
                            {{ __('Alle Besuche öffnen') }}
                        </a>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
