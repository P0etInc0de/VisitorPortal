<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Übersicht') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:h-[calc(100dvh-8.75rem)] lg:min-h-0 lg:grid-rows-[auto_minmax(0,1fr)]">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 lg:shrink-0">
            @foreach ($stats as $stat)
                <article class="rounded-3xl border border-base-300 bg-base-100 shadow-sm rounded-[1.2rem]">
                    <div class="p-4 sm:p-[1.05rem]">
                        <div class="text-sm text-base-content/65">{{ $stat['label'] }}</div>
                        <div class="mt-1.5 text-[clamp(1.8rem,1.25rem+0.7vw,2.5rem)] font-bold leading-none tracking-tight text-base-content">
                            {{ $stat['value'] }}
                        </div>
                        <div class="mt-2 text-sm text-base-content/70">{{ $stat['meta'] }}</div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid items-start gap-4 lg:min-h-0 xl:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.72fr)]">
            <article class="rounded-3xl border border-base-300 bg-base-100 shadow-sm rounded-[1.35rem] lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:overflow-hidden">
                <div class="px-4 pt-4 sm:px-[1.05rem] sm:pt-[1.05rem]">
                    <div class="mb-3 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-[1.35rem] font-bold leading-tight tracking-tight text-base-content">
                                {{ __('Anstehende Besuche') }}
                            </h2>
                            <p class="mt-1 text-sm text-base-content/65">
                                {{ __('Laufende Besuche sowie Termine bis zu 30 Tage im Voraus, maximal 15 Einträge.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:overscroll-contain">
                    <div class="grid gap-3 px-4 pb-1 sm:px-[1.05rem] sm:pb-1">
                        @forelse ($visits as $visit)
                            <article class="grid gap-3 rounded-[1.1rem] border border-base-300 bg-base-100/95 p-4 xl:grid-cols-[5rem_minmax(0,1fr)_auto] xl:items-start xl:gap-3.5">
                                <div class="flex flex-col self-stretch">
                                    <div class="text-[1.22rem] font-bold leading-none tracking-tight text-base-content">{{ $visit['time'] }}</div>
                                    <div class="mt-0.5 text-sm text-base-content/65">{{ $visit['date'] }}</div>
                                </div>

                                <div class="grid min-w-0 gap-1">
                                    <div class="flex flex-col gap-2 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0">
                                            <div class="truncate text-base font-bold leading-tight tracking-tight text-base-content">
                                                {{ $visit['title'] }}
                                            </div>
                                            <div class="mt-0.5 text-sm text-base-content/70">
                                                {{ __('Host: :name', ['name' => $visit['host']]) }}
                                            </div>
                                        </div>

                                        <span class="badge {{ $visit['status_class'] }} shrink-0 rounded-full px-3 py-1.5 text-[0.77rem] font-semibold">
                                            {{ $visit['status_label'] }}
                                        </span>
                                    </div>

                                    @if ($visit['participant_preview']->isNotEmpty() || $visit['remaining_participants'] > 0)
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach ($visit['participant_preview'] as $participantName)
                                                <span class="inline-flex items-center rounded-full border border-base-300 bg-base-200/60 px-2.5 py-1 text-xs leading-tight text-base-content/75">
                                                    {{ $participantName }}
                                                </span>
                                            @endforeach

                                            @if ($visit['remaining_participants'] > 0)
                                                <span class="inline-flex items-center rounded-full border border-base-300 bg-base-200/60 px-2.5 py-1 text-xs leading-tight text-base-content/75">
                                                    {{ __('+:count weitere', ['count' => $visit['remaining_participants']]) }}
                                                </span>
                                            @endif
                                        </div>
                                    @elseif ($visit['participant_count'] > 0)
                                        <div class="mt-1 text-xs text-base-content/65">
                                            {{ __(':count Teilnehmende', ['count' => $visit['participant_count']]) }}
                                        </div>
                                    @endif

                                    @if (filled($visit['note']))
                                        <div class="mt-1 text-sm leading-6 text-base-content/85">{{ $visit['note'] }}</div>
                                    @endif
                                </div>

                                <div class="grid justify-items-start gap-2 xl:justify-items-end">
                                    <a href="{{ route('portal.visits.show', $visit['visit']) }}" class="btn btn-outline btn-sm rounded-xl">
                                        {{ __('Details öffnen') }}
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1rem] border border-dashed border-base-300 bg-base-100/80 px-4 py-6 text-sm text-base-content/65">
                                {{ __('Aktuell sind keine anstehenden Besuche vorhanden.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

            <aside class="rounded-3xl border border-base-300 bg-base-100 shadow-sm rounded-[1.35rem] lg:min-h-0">
                <div class="p-4 sm:p-[1.05rem] lg:flex lg:h-full lg:min-h-0 lg:flex-col">
                    <h2 class="mb-3 text-[1.35rem] font-bold leading-tight tracking-tight text-base-content">
                        {{ __('Status & Benachrichtigungen') }}
                    </h2>

                    <div class="grid gap-3 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:overscroll-contain">
                        @forelse ($notifications as $notification)
                            <article @class([
                                'rounded-2xl border px-4 py-3 transition-colors',
                                'border-base-300 bg-base-100/95 shadow-sm' => ! $notification['is_read'],
                                'border-base-300 bg-base-200/35 text-base-content/60' => $notification['is_read'],
                            ])>
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <h3 @class([
                                                'font-semibold',
                                                'text-base-content' => ! $notification['is_read'],
                                                'text-base-content/60' => $notification['is_read'],
                                            ])>
                                                {{ $notification['title'] }}
                                            </h3>

                                            @if (filled($notification['created_at']))
                                                <span class="text-xs text-base-content/50">{{ $notification['created_at'] }}</span>
                                            @endif
                                        </div>

                                        <p @class([
                                            'mt-1 text-sm leading-6',
                                            'text-base-content/85' => ! $notification['is_read'],
                                            'text-base-content/55' => $notification['is_read'],
                                        ])>
                                            {{ $notification['message'] }}
                                        </p>

                                        @if (filled($notification['action_url']))
                                            <a
                                                href="{{ $notification['action_url'] }}"
                                                class="mt-2 inline-flex text-sm font-medium text-primary hover:text-primary/80"
                                            >
                                                {{ $notification['action_label'] }}
                                            </a>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1">
                                        @unless ($notification['is_read'])
                                            <form method="POST" action="{{ route('portal.notifications.read', $notification['id']) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="btn btn-ghost btn-sm btn-square rounded-xl"
                                                    aria-label="{{ __('Als gelesen markieren') }}"
                                                    title="{{ __('Als gelesen markieren') }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                                        <circle cx="12" cy="12" r="3" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endunless

                                        <form method="POST" action="{{ route('portal.notifications.destroy', $notification['id']) }}">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="btn btn-ghost btn-sm btn-square rounded-xl"
                                                aria-label="{{ __('Benachrichtigung löschen') }}"
                                                title="{{ __('Benachrichtigung löschen') }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7l1 12h10l1-12" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4h6v3" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-base-300 bg-base-100/80 px-4 py-6 text-sm leading-6 text-base-content/65">
                                {{ __('Aktuell gibt es keine Benachrichtigungen.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>

        </section>
    </div>
</x-app-layout>
