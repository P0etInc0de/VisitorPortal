<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">{{ __('Alle Besuche') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content">
        @if (session('status'))
            <div class="alert alert-success rounded-2xl">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="bp-stat-grid">
            @foreach ($stats as $stat)
                <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-sm font-medium text-base-content/65">{{ $stat['label'] }}</div>
                        <div class="mt-2 text-4xl font-semibold leading-none tracking-tight text-base-content">{{ $stat['value'] }}</div>
                        <div class="mt-2 text-sm text-base-content/70">{{ $stat['meta'] }}</div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="bp-main-grid">
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <form method="GET" action="{{ route('reception.all-visits') }}" class="mb-4 flex flex-col gap-3">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Besuchsliste') }}</h2>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($filters as $value => $label)
                                    <button type="submit" name="range" value="{{ $value }}" class="btn btn-sm rounded-xl {{ $selectedRange === $value ? 'btn-primary' : 'btn-outline' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <label class="input input-bordered flex h-11 w-full items-center gap-2 rounded-2xl">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-base-content/50">
                                <circle cx="11" cy="11" r="6" />
                                <path d="M20 20l-3.5-3.5" />
                            </svg>
                            <input type="search" name="q" value="{{ $search }}" class="grow" placeholder="{{ __('Nach Titel, Person, Firma oder Host suchen') }}">
                        </label>
                    </form>

                    <div class="space-y-4">
                        @forelse ($visits as $visit)
                            <article class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4" x-data="{ selectedVisitorId: {{ $visit->visitors->first()?->id ?? 'null' }} }">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex gap-4">
                                        <div class="min-w-[72px] shrink-0">
                                            <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">{{ $visit->scheduled_from->format('H:i') }}</div>
                                            <div class="mt-1 text-sm text-base-content/60">{{ $visit->scheduled_from->format('d.m.') }}</div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="text-xl font-semibold tracking-tight text-base-content">{{ $visit->display_title }}</div>
                                            <div class="mt-1 text-base text-base-content/72">{{ __('Host: :name', ['name' => $visit->host?->name ?? __('Nicht gesetzt')]) }}</div>
                                            @if (filled($visit->notes))
                                                <div class="mt-3 text-sm text-base-content/70">{{ $visit->notes }}</div>
                                            @endif

                                            <div class="mt-4 space-y-2">
                                                @foreach ($visit->visitors->take(3) as $visitor)
                                                    <button
                                                        type="button"
                                                        class="flex w-full items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition"
                                                        :class="selectedVisitorId === {{ $visitor->id }} ? 'border-primary bg-primary/5' : 'border-base-300 bg-base-100'"
                                                        @click="selectedVisitorId = {{ $visitor->id }}"
                                                    >
                                                        <div>
                                                            <div class="font-medium text-base-content">{{ $visitor->full_name }}</div>
                                                            <div class="text-sm text-base-content/65">{{ $visitor->display_company }}</div>
                                                        </div>
                                                        <span class="{{ $visitor->participation_badge_class }} px-3 py-2 text-xs font-semibold">{{ $visitor->participation_status_label }}</span>
                                                    </button>
                                                @endforeach

                                                @if ($visit->visitors->count() > 3)
                                                    <details class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3">
                                                        <summary class="cursor-pointer text-sm font-medium text-base-content/75">{{ __('+:count weitere Teilnehmende', ['count' => $visit->visitors->count() - 3]) }}</summary>
                                                        <div class="mt-3 space-y-2">
                                                            @foreach ($visit->visitors->slice(3) as $visitor)
                                                                <button
                                                                    type="button"
                                                                    class="flex w-full items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition"
                                                                    :class="selectedVisitorId === {{ $visitor->id }} ? 'border-primary bg-primary/5' : 'border-base-300 bg-base-100'"
                                                                    @click="selectedVisitorId = {{ $visitor->id }}"
                                                                >
                                                                    <div>
                                                                        <div class="font-medium text-base-content">{{ $visitor->full_name }}</div>
                                                                        <div class="text-sm text-base-content/65">{{ $visitor->display_company }}</div>
                                                                    </div>
                                                                    <span class="{{ $visitor->participation_badge_class }} px-3 py-2 text-xs font-semibold">{{ $visitor->participation_status_label }}</span>
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                        <a href="{{ route('portal.visits.show', $visit) }}" class="btn btn-outline btn-sm rounded-xl">{{ __('Details') }}</a>
                                        <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline btn-sm rounded-xl">{{ __('Bearbeiten') }}</a>

                                        @foreach ($visit->visitors as $visitor)
                                            <div x-show="selectedVisitorId === {{ $visitor->id }}" class="space-y-2" style="display: none;">
                                                @if ($visitor->participation_status === 'checked_in')
                                                    <form method="POST" action="{{ route('reception.participants.check-out', [$visit, $visitor]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm w-full rounded-xl">{{ __('Check-out') }}</button>
                                                    </form>
                                                @elseif ($visitor->participation_status !== 'checked_out')
                                                    <form method="POST" action="{{ route('reception.participants.check-in', [$visit, $visitor]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm w-full rounded-xl">{{ __('Check-in') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-base-300 px-5 py-8 text-sm text-base-content/65">
                                {{ __('Für den gewählten Zeitraum wurden keine Besuche gefunden.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

            <aside class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">{{ __('Vorbereitung') }}</h2>

                    <div class="space-y-3">
                        <button type="button" class="btn btn-outline w-full rounded-xl" disabled>
                            {{ __('Badge für diese Woche vorbereiten') }}
                        </button>
                        <a href="{{ route('portal.visits.create') }}" class="btn btn-outline w-full rounded-xl">{{ __('Zusätzlichen Besuch erfassen') }}</a>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
