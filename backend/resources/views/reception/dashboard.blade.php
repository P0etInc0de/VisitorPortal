<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
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

        <section>
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Heutige Termine') }}
                        </h2>
                    </div>

                    @if ($visits->isEmpty())
                        <div class="rounded-[1.5rem] border border-dashed border-base-300 bg-base-100 px-5 py-10 text-center text-base-content/65">
                            {{ __('Für heute sind aktuell keine Termine geplant.') }}
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($visits as $visit)
                                <article class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <h3 class="text-[1.65rem] font-semibold leading-tight tracking-tight text-base-content">
                                                        {{ $visit['title'] }}
                                                    </h3>

                                                    <span class="badge badge-ghost rounded-full px-3 py-2 text-sm font-medium">
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

                                        <div class="overflow-hidden rounded-2xl border border-base-300 bg-base-100">
                                            <div class="divide-y divide-base-300">
                                                @foreach ($visit['visible_participants'] as $participant)
                                                    <div class="px-4 py-3">
                                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                            <div class="min-w-0">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                                    <span class="badge {{ $participant['status_class'] }} rounded-full px-3 py-2 text-xs font-semibold">
                                                                        {{ $participant['status_label'] }}
                                                                    </span>
                                                                </div>

                                                                <div class="mt-1 text-sm text-base-content/65">
                                                                    {{ $participant['company'] ?: __('Keine Firma hinterlegt') }}
                                                                </div>
                                                            </div>

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
                                        </div>

                                        @if ($visit['hidden_count'] > 0)
                                            <details class="rounded-2xl border border-base-300 bg-base-100">
                                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-base-content/75">
                                                    + {{ $visit['hidden_count'] }} {{ __('weitere Teilnehmende') }}
                                                </summary>

                                                <div class="divide-y divide-base-300 border-t border-base-300">
                                                    @foreach ($visit['hidden_participants'] as $participant)
                                                        <div class="px-4 py-3">
                                                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                                <div class="min-w-0">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                                        <span class="badge {{ $participant['status_class'] }} rounded-full px-3 py-2 text-xs font-semibold">
                                                                            {{ $participant['status_label'] }}
                                                                        </span>
                                                                    </div>

                                                                    <div class="mt-1 text-sm text-base-content/65">
                                                                        {{ $participant['company'] ?: __('Keine Firma hinterlegt') }}
                                                                    </div>
                                                                </div>

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
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
