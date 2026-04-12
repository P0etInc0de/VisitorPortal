@php
    $ranges = $ranges ?? [];
    $activeRange = $activeRange ?? null;
    $search = $search ?? '';
    $dateFrom = $dateFrom ?? request('from', '');
    $dateTo = $dateTo ?? request('to', '');
    $rangeDateValues = [
        'today' => [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ],
        'week' => [
            'from' => now()->startOfWeek()->toDateString(),
            'to' => now()->endOfWeek()->toDateString(),
        ],
        'next-week' => [
            'from' => now()->copy()->addWeek()->startOfWeek()->toDateString(),
            'to' => now()->copy()->addWeek()->endOfWeek()->toDateString(),
        ],
        'all' => [
            'from' => '',
            'to' => '',
        ],
    ];
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

        .bp-topbar-surface.bp-page-header > .mx-auto {
            padding-top: .42rem !important;
            padding-bottom: .22rem !important;
        }

        .bp-page {
            padding-top: .22rem !important;
            padding-bottom: 1.25rem;
        }

        .bp-screen-title {
            margin: 0;
        }

        .bp-av-page {
            display: grid;
            grid-template-rows: auto auto minmax(0, 1fr);
            gap: .78rem;
            min-height: calc(100dvh - 7.35rem);
            max-height: calc(100dvh - 7.35rem);
            overflow: visible;
        }

        .bp-av-toolbar {
            display: grid;
            grid-template-columns: minmax(17rem, 1.18fr) auto;
            gap: .65rem;
            align-items: center;
        }

        .bp-av-search-form,
        .bp-av-date-form {
            margin: 0;
        }

        .bp-av-search {
            position: relative;
        }

        .bp-av-search svg {
            position: absolute;
            left: .95rem;
            top: 50%;
            width: 1rem;
            height: 1rem;
            transform: translateY(-50%);
            color: hsl(var(--bc) / .56);
            pointer-events: none;
            z-index: 1;
        }

        .bp-av-search .input {
            width: 100%;
            padding-left: 2.6rem;
            min-height: 2.4rem;
            height: 2.4rem;
            border-radius: 1.15rem;
        }

        .bp-av-date-form {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: nowrap;
            white-space: nowrap;
            min-width: max-content;
        }

        .bp-av-date-form .input {
            width: 9rem;
        }

        .bp-av-date-form .input,
        .bp-av-date-form .btn,
        .bp-av-filter-bar .btn {
            min-height: 2.35rem;
            height: 2.35rem;
            padding-top: 0;
            padding-bottom: 0;
            border-radius: 1rem;
        }

        .bp-av-date-form .btn {
            padding-left: .85rem;
            padding-right: .85rem;
        }

        .bp-av-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .bp-av-filter-bar .btn {
            padding-left: .9rem;
            padding-right: .9rem;
        }

        .bp-av-table-card {
            min-height: 0;
            height: 100%;
            border: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            border-radius: 1.35rem;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        }

        .bp-av-table-wrap {
            min-height: 0;
            height: 100%;
            overflow: auto;
        }

        .bp-av-table {
            min-width: 67rem;
            width: 100%;
        }

        .bp-av-table-head,
        .bp-av-visit-row,
        .bp-av-participant-head,
        .bp-av-participant-row {
            display: grid;
            align-items: center;
        }

        .bp-av-table-head,
        .bp-av-visit-row {
            grid-template-columns: 2rem minmax(10.5rem, 1.35fr) minmax(9.75rem, 1.02fr) minmax(6.75rem, .72fr) minmax(6.5rem, .72fr) 3.9rem minmax(8.5rem, 1.1fr) minmax(10.75rem, 1fr) 6.75rem;
            gap: .55rem;
        }

        .bp-av-table-head {
            position: sticky;
            top: 0;
            z-index: 5;
            padding: .46rem .85rem;
            border-bottom: 1px solid hsl(var(--b3));
            background: hsl(var(--b1));
            color: hsl(var(--bc) / .66);
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .bp-av-table-head .bp-av-cell,
        .bp-av-visit-row .bp-av-cell {
            justify-content: flex-start;
        }

        .bp-av-entry {
            border-bottom: 1px solid hsl(var(--b3));
        }

        .bp-av-entry:last-child {
            border-bottom: 0;
        }

        .bp-av-visit-row {
            padding: .34rem .85rem;
            min-height: 2.9rem;
            background: hsl(var(--b1));
        }

        .bp-av-entry:hover .bp-av-visit-row {
            background: hsl(var(--b2) / .38);
        }

        .bp-av-cell {
            min-width: 0;
            display: flex;
            align-items: center;
        }

        .bp-av-cell-check {
            justify-content: center;
        }

        .bp-av-checkbox {
            width: 1rem;
            height: 1rem;
            border-radius: .35rem;
        }

        .bp-av-title-wrap {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .bp-av-title {
            font-size: .92rem;
            font-weight: 700;
            line-height: 1.12;
            color: hsl(var(--bc));
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-date-label,
        .bp-av-host,
        .bp-av-count,
        .bp-av-note,
        .bp-av-participant-company,
        .bp-av-participant-status {
            font-size: .84rem;
            line-height: 1.18;
            color: hsl(var(--bc) / .72);
        }

        .bp-av-date-label,
        .bp-av-host,
        .bp-av-note,
        .bp-av-participant-name,
        .bp-av-participant-company,
        .bp-av-participant-status {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-note {
            display: block;
        }

        .bp-av-count {
            justify-content: flex-start;
            font-weight: 600;
            color: hsl(var(--bc) / .82);
        }

        .bp-av-status-text {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
            max-width: 100%;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bp-av-table-head .bp-av-cell:nth-child(6),
        .bp-av-visit-row .bp-av-cell:nth-child(6) {
            justify-content: flex-start;
        }

        .bp-av-table-head .bp-av-cell:nth-child(8),
        .bp-av-table-head .bp-av-cell:nth-child(9) {
            padding-left: .1rem;
        }

        .bp-av-status-text::before,
        .bp-status-pill::before {
            content: none !important;
            display: none !important;
        }

        .bp-av-actions {
            display: inline-flex;
            gap: .35rem;
            justify-content: flex-start;
        }

        .bp-av-head-btn,
        .bp-av-toggle-btn,
        .bp-av-row-btn {
            min-height: 1.8rem;
            height: 1.8rem;
            padding-top: 0;
            padding-bottom: 0;
            border-radius: .85rem;
            white-space: nowrap;
        }

        .bp-av-head-btn {
            min-width: 5.35rem;
        }

        .bp-av-toggle-btn {
            width: 100%;
            justify-content: center;
        }

        .bp-av-year-divider {
            position: sticky;
            top: 2.1rem;
            z-index: 4;
            padding: .38rem .85rem .28rem;
            background: linear-gradient(to bottom, hsl(var(--b1)) 0%, hsl(var(--b1)) 65%, hsl(var(--b1) / 0) 100%);
        }

        .bp-av-year-divider span {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: hsl(var(--bc) / .62);
        }

        .bp-av-year-divider span::after {
            content: '';
            width: 3.5rem;
            height: 1px;
            background: hsl(var(--b3));
        }

        .bp-av-entry-details {
            padding: 0 .85rem .38rem 3.15rem;
            background: hsl(var(--b2) / .22);
        }

        .bp-av-entry-details[hidden] {
            display: none !important;
        }

        .bp-av-participant-table {
            border: 1px solid hsl(var(--b3));
            border-radius: .9rem;
            background: hsl(var(--b1));
            overflow: hidden;
        }

        .bp-av-participant-head,
        .bp-av-participant-row {
            grid-template-columns: minmax(11rem, 1.2fr) minmax(9rem, 1fr) minmax(7rem, .8fr) 6.5rem 8rem;
            gap: .45rem;
            padding: .28rem .65rem;
        }

        .bp-av-participant-head {
            background: hsl(var(--b2) / .5);
            border-bottom: 1px solid hsl(var(--b3));
            color: hsl(var(--bc) / .64);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .bp-av-participant-row + .bp-av-participant-row {
            border-top: 1px solid hsl(var(--b3));
        }

        .bp-av-participant-name {
            font-size: .86rem;
            color: hsl(var(--bc));
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

        .bp-av-row-btn {
            width: 100%;
            justify-content: center;
            text-align: center;
            padding-left: .7rem;
            padding-right: .7rem;
        }

        .bp-av-empty {
            padding: .7rem .85rem;
            color: hsl(var(--bc) / .7);
            font-size: .88rem;
        }

        @media (max-width: 1400px) {
            .bp-av-toolbar {
                grid-template-columns: minmax(14rem, 1fr) auto;
            }
        }

        @media (max-width: 1100px) {
            .bp-av-page {
                min-height: auto;
                max-height: none;
                overflow: visible;
            }

            .bp-av-toolbar {
                grid-template-columns: 1fr;
            }

            .bp-av-date-form {
                flex-wrap: wrap;
            }

            .bp-av-table-card,
            .bp-av-table-wrap {
                min-height: auto;
                height: auto;
            }
        }
    </style>

    <div class="bp-content bp-av-page">
        <div class="bp-av-toolbar">
            <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-search-form">
                <label class="bp-search bp-av-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="6" />
                        <path d="M20 20l-3.5-3.5" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        class="input input-bordered"
                        placeholder="{{ __('Suche') }}"
                    >
                    <input type="hidden" name="range" value="{{ $activeRange ?: 'week' }}">
                    @if ($dateFrom !== '')
                        <input type="hidden" name="from" value="{{ $dateFrom }}">
                    @endif
                    @if ($dateTo !== '')
                        <input type="hidden" name="to" value="{{ $dateTo }}">
                    @endif
                </label>
            </form>

            <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-date-form" data-bp-reset-url="{{ route('reception.all-visits', array_filter(['range' => 'all', 'q' => $search ?: null], fn ($value) => $value !== null && $value !== '')) }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ $dateFrom }}" class="input input-bordered input-sm">
                <span>{{ __('bis') }}</span>
                <input type="date" name="to" value="{{ $dateTo }}" class="input input-bordered input-sm">
                <button type="submit" class="btn btn-outline btn-sm">{{ __('Anwenden') }}</button>
                <a href="{{ route('reception.all-visits', array_filter(['range' => 'all', 'q' => $search ?: null], fn ($value) => $value !== null && $value !== '')) }}" class="btn btn-outline btn-sm">{{ __('Zurücksetzen') }}</a>
            </form>
        </div>

        <div class="bp-av-filter-bar">
            @foreach ($ranges as $value => $label)
                @php
                    $rangeParams = [
                        'range' => $value,
                        'q' => $search ?: null,
                    ];

                    if ($value !== 'all') {
                        $rangeParams['from'] = $rangeDateValues[$value]['from'] ?? null;
                        $rangeParams['to'] = $rangeDateValues[$value]['to'] ?? null;
                    }
                @endphp
                <a href="{{ route('reception.all-visits', array_filter($rangeParams, fn ($rangeValue) => $rangeValue !== null && $rangeValue !== '')) }}" class="btn btn-sm {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
            @endforeach
        </div>

        <section class="bp-av-table-card">
            <div class="bp-av-table-wrap">
                <div class="bp-av-table">
                    <div class="bp-av-table-head">
                        <div class="bp-av-cell bp-av-cell-check">
                            <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" data-bp-select-all aria-label="{{ __('Alle Besuche auswählen') }}">
                        </div>
                        <div class="bp-av-cell">{{ __('Termin') }}</div>
                        <div class="bp-av-cell">{{ __('Datum / Zeit') }}</div>
                        <div class="bp-av-cell">{{ __('Status') }}</div>
                        <div class="bp-av-cell">{{ __('Host') }}</div>
                        <div class="bp-av-cell">{{ __('Teiln.') }}</div>
                        <div class="bp-av-cell">{{ __('Notiz') }}</div>
                        <div class="bp-av-cell">{{ __('Aktionen') }}</div>
                        <div class="bp-av-cell">{{ __('Besucher') }}</div>
                    </div>

                    <div id="bp-av-visit-stack">
                        @php
                            $previousYear = null;
                            $weekdayMap = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
                        @endphp

                        @forelse ($visits as $index => $visit)
                            @php
                                $scheduledFrom = null;
                                $visitDateLabel = null;
                                $yearLabel = null;

                                if (! empty($visit['scheduled_from'])) {
                                    try {
                                        $scheduledFrom = \Illuminate\Support\Carbon::createFromFormat('d.m.Y H:i', $visit['scheduled_from']);
                                    } catch (\Throwable $e) {
                                        $scheduledFrom = null;
                                    }
                                }

                                if ($scheduledFrom) {
                                    $visitDateLabel = ($weekdayMap[$scheduledFrom->dayOfWeekIso] ?? $scheduledFrom->isoFormat('dd')).', '.$scheduledFrom->format('d.m.').' | '.$scheduledFrom->format('H:i').' '.__('Uhr');
                                    $yearLabel = $scheduledFrom->format('Y');
                                } elseif (! empty($visit['date']) && ! empty($visit['time'])) {
                                    $visitDateLabel = $visit['date'].' | '.$visit['time'].' '.__('Uhr');
                                }

                                if ($yearLabel === null) {
                                    $yearLabel = __('Ohne Jahr');
                                }

                                if (is_array($visit['status'] ?? null)) {
                                    $visitStatusLabel = $visit['status']['label'] ?? '';
                                    $visitStatusClass = $visit['status']['class'] ?? '';
                                } else {
                                    $visitStatusLabel = (string) ($visit['status'] ?? '');
                                    $visitStatusClass = '';
                                }

                                $visitStatusLabel = preg_replace('/^\s*[•·]\s*/u', '', $visitStatusLabel);
                                $visitStatusClass = trim(str_replace('bp-status-pill', '', $visitStatusClass));
                                $participants = $visit['participants'] ?? array_merge($visit['visible_participants'] ?? [], $visit['hidden_participants'] ?? []);
                                $detailId = 'bp-av-details-'.$index.'-'.($visit['id'] ?? $index);
                                $noteText = trim((string) ($visit['notes'] ?? ($visit['note'] ?? '')));
                            @endphp

                            @if ($yearLabel !== $previousYear)
                                <div class="bp-av-year-divider"><span>{{ $yearLabel }}</span></div>
                                @php $previousYear = $yearLabel; @endphp
                            @endif

                            <article class="bp-av-entry">
                                <div class="bp-av-visit-row">
                                    <div class="bp-av-cell bp-av-cell-check">
                                        <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" name="selected_visits[]" value="{{ $visit['id'] ?? $index }}" data-bp-select-item aria-label="{{ __('Besuch auswählen') }}">
                                    </div>

                                    <div class="bp-av-cell">
                                        <div class="bp-av-title-wrap">
                                            <span class="bp-av-title">{{ $visit['title'] }}</span>
                                        </div>
                                    </div>

                                    <div class="bp-av-cell">
                                        <span class="bp-av-date-label">{{ $visitDateLabel ?: '—' }}</span>
                                    </div>

                                    <div class="bp-av-cell">
                                        @if ($visitStatusLabel !== '')
                                            <span class="bp-av-status-text {{ $visitStatusClass }}">{{ $visitStatusLabel }}</span>
                                        @else
                                            <span class="bp-av-status-text">—</span>
                                        @endif
                                    </div>

                                    <div class="bp-av-cell">
                                        <span class="bp-av-host">{{ $visit['host'] ?? '—' }}</span>
                                    </div>

                                    <div class="bp-av-cell">
                                        <span class="bp-av-count">{{ $visit['participants_count'] ?? $visit['participant_count'] ?? count($participants) }}</span>
                                    </div>

                                    <div class="bp-av-cell">
                                        <span class="bp-av-note" title="{{ $noteText !== '' ? $noteText : '—' }}">{{ $noteText !== '' ? $noteText : '—' }}</span>
                                    </div>

                                    <div class="bp-av-cell">
                                        <div class="bp-av-actions">
                                            <a href="{{ $visit['open_url'] ?? route('portal.visits.show', $visit['id']) }}" class="btn btn-outline btn-sm bp-av-head-btn" data-bp-no-toggle>{{ __('Öffnen') }}</a>
                                            <a href="{{ $visit['edit_url'] ?? route('portal.visits.edit', $visit['id']) }}" class="btn btn-outline btn-sm bp-av-head-btn" data-bp-no-toggle>{{ __('Bearbeiten') }}</a>
                                        </div>
                                    </div>

                                    <div class="bp-av-cell">
                                        <button type="button" class="btn btn-outline btn-sm bp-av-toggle-btn" data-bp-toggle-details="{{ $detailId }}" aria-expanded="false">
                                            {{ __('Anzeigen') }}
                                        </button>
                                    </div>
                                </div>

                                <div id="{{ $detailId }}" class="bp-av-entry-details" hidden>
                                    <div class="bp-av-participant-table">
                                        <div class="bp-av-participant-head">
                                            <div>{{ __('Name') }}</div>
                                            <div>{{ __('Firma') }}</div>
                                            <div>{{ __('Status') }}</div>
                                            <div>{{ __('Ausweis') }}</div>
                                            <div>{{ __('Aktion') }}</div>
                                        </div>

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
                                                    $statusLabel = $rawStatusLabel ? preg_replace('/^\s*[•·]\s*/u', '', (string) $rawStatusLabel) : '—';
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

                                                    echo '<div class="bp-av-participant-row">';
                                                    echo '<div class="bp-av-cell"><span class="bp-av-participant-name">'.e($displayName).'</span></div>';
                                                    echo '<div class="bp-av-cell"><span class="bp-av-participant-company">'.e($participant['company'] ?? '—').'</span></div>';
                                                    echo '<div class="bp-av-cell"><span class="bp-av-status-text '.e($statusClass).'">'.e($statusLabel).'</span></div>';
                                                    echo '<div class="bp-av-cell bp-av-badge-cell">';
                                                    if ($badgeUrl) {
                                                        echo '<form method="POST" action="'.e($badgeUrl).'">';
                                                        echo csrf_field();
                                                        echo '<button type="submit" class="btn btn-outline btn-sm bp-av-row-btn">'.e(__('Ausweis')).'</button>';
                                                        echo '</form>';
                                                    } else {
                                                        echo '<button type="button" class="btn btn-outline btn-sm bp-av-row-btn">'.e(__('Ausweis')).'</button>';
                                                    }
                                                    echo '</div>';
                                                    echo '<div class="bp-av-cell bp-av-action-cell">';
                                                    if ($canCheckIn && $checkInUrl) {
                                                        echo '<form method="POST" action="'.e($checkInUrl).'" data-bp-ajax="participant-action">';
                                                        echo csrf_field();
                                                        echo '<button type="submit" class="btn btn-primary btn-sm bp-av-row-btn">'.e($checkInLabel).'</button>';
                                                        echo '</form>';
                                                    } elseif ($canCheckOut && $checkOutUrl) {
                                                        echo '<form method="POST" action="'.e($checkOutUrl).'" data-bp-ajax="participant-action">';
                                                        echo csrf_field();
                                                        echo '<button type="submit" class="btn btn-primary btn-sm bp-av-row-btn">'.e(__('Check-out')).'</button>';
                                                        echo '</form>';
                                                    } else {
                                                        echo '<span class="bp-av-participant-status">—</span>';
                                                    }
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                            };
                                        @endphp

                                        @if (count($participants) > 0)
                                            {!! $renderParticipants($participants) !!}
                                        @else
                                            <div class="bp-av-empty">{{ __('Keine Teilnehmenden vorhanden.') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="bp-av-empty">{{ __('Keine Besuche gefunden.') }}</div>
                        @endforelse
                    </div>
                </div>
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

            document.addEventListener('click', (event) => {
                const toggleButton = event.target.closest('[data-bp-toggle-details]');

                if (toggleButton) {
                    const targetId = toggleButton.getAttribute('data-bp-toggle-details');
                    const target = document.getElementById(targetId);

                    if (! target) {
                        return;
                    }

                    const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                    toggleButton.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                    toggleButton.textContent = isExpanded ? @json(__('Anzeigen')) : @json(__('Verbergen'));
                    target.hidden = isExpanded;
                    return;
                }
            });

            document.addEventListener('change', (event) => {
                const selectAll = event.target.closest('[data-bp-select-all]');

                if (selectAll) {
                    document.querySelectorAll('[data-bp-select-item]').forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    return;
                }

                if (event.target.matches('[data-bp-select-item]')) {
                    const items = Array.from(document.querySelectorAll('[data-bp-select-item]'));
                    const selectAllBox = document.querySelector('[data-bp-select-all]');

                    if (selectAllBox) {
                        selectAllBox.checked = items.length > 0 && items.every((checkbox) => checkbox.checked);
                    }
                }
            });

            document.addEventListener('submit', async (event) => {
                const dateForm = event.target.closest('.bp-av-date-form');

                if (dateForm) {
                    const fromInput = dateForm.querySelector('input[name="from"]');
                    const toInput = dateForm.querySelector('input[name="to"]');
                    const fromValue = fromInput ? fromInput.value.trim() : '';
                    const toValue = toInput ? toInput.value.trim() : '';

                    if (fromValue === '' && toValue === '') {
                        event.preventDefault();
                        window.location.href = dateForm.dataset.bpResetUrl || dateForm.action;
                    }

                    return;
                }

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
