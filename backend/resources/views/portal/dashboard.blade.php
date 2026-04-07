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
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Anstehende Besuche') }}</h2>

                        <a href="{{ route('portal.visits.create') }}" class="btn btn-outline btn-sm rounded-xl">
                            {{ __('Neuen Besuch anlegen') }}
                        </a>
                    </div>

                    <div class="space-y-4">
                        @forelse ($visits as $visit)
                            <article class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex gap-4">
                                        <div class="min-w-[72px] shrink-0">
                                            <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">
                                                {{ optional($visit->scheduled_from)->format('H:i') ?: '–' }}
                                            </div>
                                            <div class="mt-1 text-sm text-base-content/60">
                                                {{ optional($visit->scheduled_from)->format('d.m.') ?: '–' }}
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="text-xl font-semibold tracking-tight text-base-content">
                                                {{ __($visit->display_title) }}
                                            </div>

                                            <div class="mt-1 text-base text-base-content/75">
                                                {{ __('Host: :name', ['name' => $visit->host?->name ?? __('Nicht gesetzt')]) }}
                                            </div>

                                            <div class="mt-3 flex flex-wrap gap-2 text-sm text-base-content/72">
                                                @foreach ($visit->participantPreview() as $visitor)
                                                    <span class="rounded-full border border-base-300 px-3 py-1">{{ $visitor->full_name }}</span>
                                                @endforeach

                                                @if ($visit->participant_count > 3)
                                                    <span class="rounded-full border border-base-300 px-3 py-1">
                                                        {{ __('+:count weitere', ['count' => $visit->participant_count - 3]) }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if (filled($visit->notes))
                                                <div class="mt-3 text-base leading-6 text-base-content/80">
                                                    {{ $visit->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                        <span class="badge badge-outline rounded-full px-3 py-3 text-sm font-semibold">
                                            {{ __($visit->status) }}
                                        </span>

                                        <a href="{{ route('portal.visits.show', $visit) }}" class="btn btn-outline btn-sm rounded-xl">
                                            {{ __('Details öffnen') }}
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-base-300 px-5 py-8 text-sm text-base-content/65">
                                {{ __('Aktuell sind keine anstehenden Besuche vorhanden.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

            <aside class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">{{ __('Notizen') }}</h2>

                    <div class="space-y-3">
                        @forelse ($notes as $note)
                            <div class="rounded-[1.35rem] border border-base-300 bg-base-100 px-4 py-4 text-base leading-7 text-base-content/85">
                                {{ $note }}
                            </div>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-base-300 px-4 py-4 text-sm text-base-content/65">
                                {{ __('Keine offenen Hinweise.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
