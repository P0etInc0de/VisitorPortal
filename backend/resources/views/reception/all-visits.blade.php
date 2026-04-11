@php
    $stats = $stats ?? [];
    $ranges = $ranges ?? [];
    $activeRange = $activeRange ?? null;
    $search = $search ?? '';
    $dateFrom = request('from', '');
    $dateTo = request('to', '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-av-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Alle Besuche') }}</h1>
            </div>
        </div>
    </x-slot>

    <style>
        .bp-topbar-surface,
        .bp-screen-head.bp-av-screen-head {
            background: hsl(var(--b1)) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            box-shadow: none !important;
            border-bottom: 0 !important;
        }

        .bp-av-page {
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            gap: 1rem;
            padding-top: .35rem;
            min-height: calc(100dvh - 8.5rem);
            max-height: calc(100dvh - 8.5rem);
            overflow: hidden;
        }

        .bp-av-header-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .85rem;
            margin-bottom: 1rem;
        }

        .bp-av-stat-card {
            border: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            border-radius: 1.45rem;
            padding: .9rem 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }

        .bp-av-stat-label {
            font-size: .92rem;
            color: hsl(var(--bc) / .72);
        }

        .bp-av-stat-value {
            margin-top: .12rem;
            font-size: 1.85rem;
            line-height: 1;
            font-weight: 700;
            color: hsl(var(--bc));
        }

        .bp-av-stat-meta {
            margin-top: .34rem;
            font-size: .9rem;
            color: hsl(var(--bc) / .72);
        }

        .bp-av-list-card {
            display: grid;
            grid-template-rows: auto auto auto minmax(0, 1fr);
            min-height: 0;
            height: 100%;
            border: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            border-radius: 1.8rem;
            padding: 1rem 1.1rem 1.1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .bp-av-list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .85rem;
        }

        .bp-av-list-title {
            margin: 0;
            font-size: 1.08rem;
            font-weight: 700;
            color: hsl(var(--bc));
        }

        .bp-av-tools {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: .8rem;
            align-items: center;
            margin-bottom: .8rem;
        }

        .bp-av-search .input {
            width: 100%;
        }

        .bp-av-print-all {
            white-space: nowrap;
        }

        .bp-av-filter-line {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .8rem;
            align-items: center;
            margin-bottom: .95rem;
        }

        .bp-av-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .bp-av-date-range {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .bp-av-date-range .input {
            width: 11rem;
        }

        .bp-av-reset-link {
            font-size: .95rem;
            color: hsl(var(--bc) / .72);
            text-decoration: none;
        }

        .bp-av-visit-stack {
            display: flex;
            flex-direction: column;
            gap: .8rem;
            min-height: 0;
            overflow-y: auto;
            padding-right: .2rem;
        }

        .bp-av-visit-card {
            border: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            border-radius: 1.45rem;
            padding: .95rem 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .bp-av-visit-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .6rem;
        }

        .bp-av-visit-title-row {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .bp-av-visit-title {
            margin: 0;
            font-size: 1rem;
            line-height: 1.2;
            font-weight: 700;
            color: hsl(var(--bc));
        }

        .bp-av-visit-time {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: .2rem .55rem;
            background: hsl(var(--b2));
            color: hsl(var(--bc) / .76);
            font-size: .82rem;
            line-height: 1;
        }

        .bp-av-visit-subline,
        .bp-av-visit-note {
            margin-top: .22rem;
            font-size: .92rem;
            color: hsl(var(--bc) / .72);
        }

        .bp-av-dot {
            padding: 0 .22rem;
        }

        .bp-av-visit-actions {
            display: flex;
            gap: .55rem;
            flex-shrink: 0;
        }

        .bp-av-head-btn {
            min-width: 7rem;
        }

        .bp-av-participants {
            border: 1px solid hsl(var(--b3));
            border-radius: 1.1rem;
            overflow: hidden;
        }

        .bp-av-row {
            display: grid;
            grid-template-columns: minmax(13rem, 15.5rem) minmax(9rem, 12rem) 6.4rem 8rem 8.8rem;
            gap: .3rem;
            align-items: center;
            justify-content: start;
            padding: .4rem .75rem;
            min-height: 2.6rem;
        }

        .bp-av-row + .bp-av-row {
            border-top: 1px solid hsl(var(--b3));
        }

        .bp-av-col {
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .bp-av-display-name,
        .bp-av-company {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-display-name {
            font-size: .96rem;
            color: hsl(var(--bc));
        }

        .bp-av-company {
            font-size: .94rem;
            color: hsl(var(--bc) / .78);
        }

        .bp-av-row-btn {
            width: 100%;
            height: 2.2rem;
            min-height: 2.2rem;
            justify-content: center;
            text-align: center;
            white-space: nowrap;
            padding-left: .75rem;
            padding-right: .75rem;
        }

        .bp-av-status-pill {
            width: 100%;
            justify-content: flex-start;
            text-align: left;
            white-space: nowrap;
        }

        .bp-av-status-pill::before,
        .bp-status-pill.bp-av-status-pill::before,
        .bp-status-pill::before,
        .bp-av-status-text::before {
            content: none !important;
            display: none !important;
        }

        .bp-av-status-text {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: flex-start;
            font-size: .96rem;
            font-weight: 600;
            line-height: 1.1;
            white-space: nowrap;
        }

        .bp-av-action-cell,
        .bp-av-badge-cell {
            width: 100%;
        }

        .bp-av-action-cell form,
        .bp-av-badge-cell form {
            width: 100%;
            margin: 0;
        }

        .bp-av-empty {
            padding: .9rem 1rem;
            color: hsl(var(--bc) / .7);
            font-size: .92rem;
        }

        @media (max-width: 1200px) {
            .bp-av-row {
                grid-template-columns: minmax(11rem, 13.5rem) minmax(8rem, 10.5rem) 6.2rem 7.6rem 8.4rem;
            }
        }

        @media (max-width: 1024px) {
            .bp-av-page {
                min-height: auto;
                max-height: none;
                overflow: visible;
            }

            .bp-av-header-summary {
                grid-template-columns: 1fr;
            }

            .bp-av-tools,
            .bp-av-filter-line {
                grid-template-columns: 1fr;
            }

            .bp-av-list-card,
            .bp-av-visit-stack {
                min-height: auto;
                height: auto;
                overflow: visible;
            }

            .bp-av-date-range {
                justify-content: flex-start;
            }

            .bp-av-visit-head {
                flex-direction: column;
                align-items: stretch;
            }

            .bp-av-row {
                grid-template-columns: 1fr;
                gap: .45rem;
                padding: .7rem .85rem;
            }
        }
    </style>

    <div class="bp-content bp-av-page">
        <div class="bp-av-header-summary" id="bp-av-header-summary">
            @foreach ($stats as $stat)
                <article class="bp-av-stat-card">
                    <div class="bp-av-stat-label">{{ $stat['label'] }}</div>
                    <div class="bp-av-stat-value">{{ $stat['value'] }}</div>
                    <div class="bp-av-stat-meta">{{ $stat['meta'] }}</div>
                </article>
            @endforeach
        </div>

        <section class="bp-av-list-card">
            <div class="bp-av-list-head">
                <h2 class="bp-av-list-title">{{ __('Besuchsliste') }}</h2>
            </div>

            <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-tools">
                <label class="bp-search bp-av-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="6" />
                        <path d="M20 20l-3.5-3.5" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        class="input input-bordered rounded-2xl"
                        placeholder="{{ __('Nach Titel, Host, Name oder Firma suchen') }}"
                    >
                    <input type="hidden" name="range" value="{{ $activeRange }}">
                    @if ($dateFrom !== '')
                        <input type="hidden" name="from" value="{{ $dateFrom }}">
                    @endif
                    @if ($dateTo !== '')
                        <input type="hidden" name="to" value="{{ $dateTo }}">
                    @endif
                </label>

                <button type="button" class="btn btn-outline rounded-2xl bp-av-print-all">{{ __('Ausweise drucken') }}</button>
            </form>

            <div class="bp-av-filter-line">
                <div class="bp-av-filter-bar">
                    @foreach ($ranges as $value => $label)
                        <a href="{{ route('reception.all-visits', array_filter(['range' => $value, 'q' => $search ?: null, 'from' => $dateFrom ?: null, 'to' => $dateTo ?: null])) }}" class="btn btn-sm rounded-xl {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-date-range">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="range" value="custom">
                    <input type="date" name="from" value="{{ $dateFrom }}" class="input input-bordered input-sm rounded-xl">
                    <span>{{ __('bis') }}</span>
                    <input type="date" name="to" value="{{ $dateTo }}" class="input input-bordered input-sm rounded-xl">
                    <button type="submit" class="btn btn-outline btn-sm rounded-xl">{{ __('Anwenden') }}</button>
                    <a href="{{ route('reception.all-visits', array_filter(['range' => $activeRange, 'q' => $search ?: null])) }}" class="bp-av-reset-link">{{ __('Zurücksetzen') }}</a>
                </form>
            </div>

            <div class="bp-av-visit-stack" id="bp-av-visit-stack">
                @forelse ($visits as $visit)
                    <article class="bp-av-visit-card">
                        <div class="bp-av-visit-head">
                            <div>
                                <div class="bp-av-visit-title-row">
                                    <h3 class="bp-av-visit-title">{{ $visit['title'] }}</h3>

                                    @if (! empty($visit['time']) && ! empty($visit['date']))
                                        <span class="bp-av-visit-time">{{ $visit['time'] }} · {{ $visit['date'] }}</span>
                                    @elseif (! empty($visit['date_time']))
                                        <span class="bp-av-visit-time">{{ $visit['date_time'] }}</span>
                                    @endif

                                    @if (! empty($visit['status']))
                                        @php
                                            if (is_array($visit['status'])) {
                                                $visitStatusLabel = $visit['status']['label'] ?? '';
                                                $visitStatusClass = $visit['status']['class'] ?? '';
                                            } else {
                                                $visitStatusLabel = (string) $visit['status'];
                                                $visitStatusClass = '';
                                            }

                                            $visitStatusLabel = preg_replace('/^\s*[•·]\s*/u', '', $visitStatusLabel);
                                            $visitStatusClass = trim(str_replace('bp-status-pill', '', $visitStatusClass));
                                        @endphp
                                        <span class="bp-av-status-text {{ $visitStatusClass }}">{{ $visitStatusLabel }}</span>
                                    @endif
                                </div>

                                <div class="bp-av-visit-subline">
                                    {{ __('Host:') }} {{ $visit['host'] ?? '—' }}
                                    @if (! empty($visit['participants_count']))
                                        <span class="bp-av-dot">•</span>
                                        {{ $visit['participants_count'] }} {{ __('Teilnehmende') }}
                                    @elseif (! empty($visit['participant_count']))
                                        <span class="bp-av-dot">•</span>
                                        {{ $visit['participant_count'] }} {{ __('Teilnehmende') }}
                                    @endif
                                </div>

                                @if (! empty($visit['notes']))
                                    <div class="bp-av-visit-note">{{ $visit['notes'] }}</div>
                                @elseif (! empty($visit['note']))
                                    <div class="bp-av-visit-note">{{ $visit['note'] }}</div>
                                @endif
                            </div>

                            <div class="bp-av-visit-actions">
                                <a href="{{ $visit['open_url'] ?? route('portal.visits.show', $visit['id']) }}" class="btn btn-outline rounded-xl bp-av-head-btn">{{ __('Öffnen') }}</a>
                                <a href="{{ $visit['edit_url'] ?? route('portal.visits.edit', $visit['id']) }}" class="btn btn-outline rounded-xl bp-av-head-btn">{{ __('Bearbeiten') }}</a>
                            </div>
                        </div>

                        @php
                            $visibleParticipants = $visit['visible_participants'] ?? ($visit['participants'] ?? []);
                            $hiddenParticipants = $visit['hidden_participants'] ?? [];
                            $hiddenCount = $visit['hidden_count'] ?? 0;
                        @endphp

                        <div class="bp-av-participants">
                            @php
                                $renderParticipants = function ($participants) {
                                    foreach ($participants as $participant) {
                                        $salutation = trim((string) ($participant['salutation'] ?? ''));
                                        $title = trim((string) ($participant['title'] ?? ''));
                                        $baseName = trim((string) ($participant['name'] ?? '—'));
                                        $displayName = trim(collect([$salutation, $title, $baseName])->filter()->implode(' '));
                                        $displayName = $displayName !== '' ? $displayName : '—';

                                        $badgeUrl = $participant['badge_url'] ?? null;
                                        $rawStatusLabel = $participant['status_label'] ?? ($participant['status']['label'] ?? ($participant['status'] ?? null));
                                        $statusLabel = $rawStatusLabel ? preg_replace('/^\s*[•·]\s*/u', '', (string) $rawStatusLabel) : null;
                                        $statusClass = trim(str_replace('bp-status-pill', '', (string) ($participant['status_class'] ?? ($participant['status']['class'] ?? ''))));

                                        $checkInUrl = $participant['check_in_url'] ?? null;
                                        $checkOutUrl = $participant['check_out_url'] ?? null;
                                        $checkInLabel = $participant['check_in_label'] ?? __('Check-in');
                                        $canCheckIn = (bool) ($participant['can_check_in'] ?? false);
                                        $canCheckOut = (bool) ($participant['can_check_out'] ?? false);

                                        if (! $canCheckIn && ! $canCheckOut && ! empty($participant['action_url'])) {
                                            $legacyActionLabel = (string) ($participant['action_label'] ?? '');
                                            $legacyActionUrl = $participant['action_url'];
                                            $legacyActionLower = mb_strtolower($legacyActionLabel);

                                            if (str_contains($legacyActionLower, 'check-out') || str_contains($legacyActionLower, 'checkout')) {
                                                $canCheckOut = true;
                                                $checkOutUrl = $legacyActionUrl;
                                            } elseif ($legacyActionLabel !== '') {
                                                $canCheckIn = true;
                                                $checkInUrl = $legacyActionUrl;
                                                $checkInLabel = $legacyActionLabel;
                                            }
                                        }

                                        echo '<div class="bp-av-row">';
                                        echo '<div class="bp-av-col"><span class="bp-av-display-name">'.e($displayName).'</span></div>';
                                        echo '<div class="bp-av-col"><span class="bp-av-company">'.e($participant['company'] ?? '—').'</span></div>';
                                        echo '<div class="bp-av-col bp-av-badge-cell">';
                                        if ($badgeUrl) {
                                            echo '<form method="POST" action="'.e($badgeUrl).'">';
                                            echo csrf_field();
                                            echo '<button type="submit" class="btn btn-outline btn-sm rounded-xl bp-av-row-btn">'.e(__('Ausweis')).'</button>';
                                            echo '</form>';
                                        } else {
                                            echo '<button type="button" class="btn btn-outline btn-sm rounded-xl bp-av-row-btn">'.e(__('Ausweis')).'</button>';
                                        }
                                        echo '</div>';
                                        echo '<div class="bp-av-col">';
                                        if ($statusLabel) {
                                            echo '<span class="bp-av-status-text '.e($statusClass).'">'.e($statusLabel).'</span>';
                                        }
                                        echo '</div>';
                                        echo '<div class="bp-av-col bp-av-action-cell">';
                                        if ($canCheckIn && $checkInUrl) {
                                            echo '<form method="POST" action="'.e($checkInUrl).'" data-bp-ajax="participant-action">';
                                            echo csrf_field();
                                            echo '<button type="submit" class="btn btn-primary btn-sm rounded-xl bp-av-row-btn">'.e($checkInLabel).'</button>';
                                            echo '</form>';
                                        } elseif ($canCheckOut && $checkOutUrl) {
                                            echo '<form method="POST" action="'.e($checkOutUrl).'" data-bp-ajax="participant-action">';
                                            echo csrf_field();
                                            echo '<button type="submit" class="btn btn-primary btn-sm rounded-xl bp-av-row-btn">'.e(__('Check-out')).'</button>';
                                            echo '</form>';
                                        }
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                };
                            @endphp

                            @if (count($visibleParticipants) > 0)
                                {!! $renderParticipants($visibleParticipants) !!}
                            @else
                                <div class="bp-av-empty">{{ __('Keine Teilnehmenden vorhanden.') }}</div>
                            @endif

                            @if ($hiddenCount > 0)
                                <details class="border-t border-base-300">
                                    <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-base-content/72">+ {{ $hiddenCount }} {{ __('weitere Teilnehmende') }}</summary>
                                    <div class="border-t border-base-300">
                                        {!! $renderParticipants($hiddenParticipants) !!}
                                    </div>
                                </details>
                            @endif
                        </div>
                    </article>
                @empty
                    <article class="bp-av-visit-card">
                        <div class="bp-av-empty">{{ __('Keine Besuche gefunden.') }}</div>
                    </article>
                @endforelse
            </div>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.querySelector('.bp-av-page');

            if (! page || page.dataset.bpAjaxReady === '1') {
                return;
            }

            page.dataset.bpAjaxReady = '1';

            const parser = new DOMParser();

            const replaceSection = (selector, doc) => {
                const current = document.querySelector(selector);
                const next = doc.querySelector(selector);

                if (current && next) {
                    current.replaceWith(next);
                }
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('form[data-bp-ajax="participant-action"]');

                if (! form) {
                    return;
                }

                event.preventDefault();

                const submitButton = form.querySelector('button[type="submit"]');

                if (submitButton) {
                    submitButton.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/xhtml+xml',
                        },
                    });

                    if (response.redirected && response.url && ! response.url.includes('/reception')) {
                        window.location.href = response.url;
                        return;
                    }

                    if (! response.ok) {
                        throw new Error('Request failed');
                    }

                    const html = await response.text();
                    const doc = parser.parseFromString(html, 'text/html');

                    if (! doc.querySelector('#bp-av-visit-stack')) {
                        window.location.reload();
                        return;
                    }

                    replaceSection('#bp-av-header-summary', doc);
                    replaceSection('#bp-av-visit-stack', doc);
                } catch (error) {
                    form.submit();
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                }
            }, true);
        });
    </script>
</x-app-layout>

