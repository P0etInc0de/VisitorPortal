<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Übersicht') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content bp-dashboard-layout">
        <section class="bp-stat-grid bp-dashboard-stats">
            @foreach ($stats as $stat)
                <article class="card bp-dashboard-stat-card rounded-[1.2rem] border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body bp-dashboard-stat-body">
                        <div class="bp-dashboard-stat-label">{{ $stat['label'] }}</div>
                        <div class="bp-dashboard-stat-value">{{ $stat['value'] }}</div>
                        <div class="bp-dashboard-stat-meta">{{ $stat['meta'] }}</div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="bp-main-grid bp-dashboard-main-grid">
            <article class="card bp-dashboard-list-card rounded-[1.35rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body bp-dashboard-list-body">
                    <div class="bp-card-header bp-dashboard-list-header">
                        <h2 class="bp-card-title">{{ __('Anstehende Besuche') }}</h2>
                    </div>

                    <div class="bp-dashboard-visit-list">
                        @forelse ($visits as $visit)
                            <article class="bp-visit-item bp-dashboard-visit-card">
                                <div class="bp-visit-timebox bp-dashboard-timebox">
                                    <div class="bp-visit-time bp-dashboard-time">{{ $visit['time'] }}</div>
                                    <div class="bp-visit-date bp-dashboard-date">{{ $visit['date'] }}</div>
                                </div>

                                <div class="bp-visit-main bp-dashboard-visit-main">
                                    <div class="bp-dashboard-visit-head">
                                        <div class="min-w-0">
                                            <div class="bp-visit-title bp-dashboard-visit-title">{{ $visit['title'] }}</div>
                                            <div class="bp-visit-meta bp-dashboard-visit-meta">
                                                {{ __('Host: :name', ['name' => $visit['host']]) }}
                                            </div>
                                        </div>

                                        <span class="bp-status-pill {{ $visit['status_class'] }} bp-dashboard-status-pill">
                                            {{ $visit['status_label'] }}
                                        </span>
                                    </div>

                                    @if ($visit['participant_preview']->isNotEmpty() || $visit['remaining_participants'] > 0)
                                        <div class="bp-dashboard-preview-row">
                                            <div class="bp-dashboard-preview-list">
                                                @foreach ($visit['participant_preview'] as $participantName)
                                                    <span class="bp-dashboard-preview-chip">{{ $participantName }}</span>
                                                @endforeach

                                                @if ($visit['remaining_participants'] > 0)
                                                    <span class="bp-dashboard-preview-more">
                                                        {{ __('+:count weitere', ['count' => $visit['remaining_participants']]) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif ($visit['participant_count'] > 0)
                                        <div class="bp-dashboard-preview-empty">
                                            {{ __(':count Teilnehmende', ['count' => $visit['participant_count']]) }}
                                        </div>
                                    @endif

                                    @if (filled($visit['note']))
                                        <div class="bp-visit-note bp-dashboard-note-text">{{ $visit['note'] }}</div>
                                    @endif
                                </div>

                                <div class="bp-visit-actions bp-dashboard-actions">
                                    <a href="{{ route('portal.visits.show', $visit['visit']) }}" class="btn btn-outline btn-sm rounded-xl">
                                        {{ __('Details öffnen') }}
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[1rem] border border-dashed border-base-300 bg-base-100 px-4 py-6 text-sm text-base-content/65">
                                {{ __('Aktuell sind keine anstehenden Besuche vorhanden.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </article>

            <aside class="card bp-dashboard-notes-card rounded-[1.35rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body bp-dashboard-notes-body">
                    <h2 class="bp-card-title bp-dashboard-notes-title">{{ __('Notizen') }}</h2>

                    <div class="bp-dashboard-notes-list">
                        @forelse ($notes as $note)
                            <div class="bp-dashboard-note-item">{{ $note }}</div>
                        @empty
                            <div class="bp-dashboard-note-empty">
                                {{ __('Aktuell gibt es keine offenen Notizen.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
