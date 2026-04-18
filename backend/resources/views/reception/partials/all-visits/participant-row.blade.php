@php
    $salutation = trim((string) ($participant['salutation'] ?? ''));
    $title = trim((string) ($participant['title'] ?? ''));
    $baseName = trim((string) ($participant['name'] ?? '—'));
    $displayName = trim(collect([$salutation, $title, $baseName])->filter()->implode(' '));
    $displayName = $displayName !== '' ? $displayName : '—';

    $badgeUrl = $participant['badge_url'] ?? null;
    $rawStatusLabel = $participant['status_label'] ?? ($participant['status']['label'] ?? ($participant['status'] ?? null));
    $statusLabel = $rawStatusLabel ? preg_replace('/^\s*[•·]\s*/u', '', (string) $rawStatusLabel) : '—';
    $statusClass = trim(str_replace('bp-status-pill', '', (string) ($participant['status_class'] ?? ($participant['status']['class'] ?? ''))));
    $checkInLabel = $participant['check_in_label'] ?? __('Check-in');
    $canCheckIn = (bool) ($participant['can_check_in'] ?? false);
    $canCheckOut = (bool) ($participant['can_check_out'] ?? false);
@endphp

<div class="bp-av-participant-row" wire:key="participant-row-{{ $participant['row_key'] ?? (($visitId ?? 'visit').'-'.($participant['visitor_id'] ?? 'visitor')) }}">
    <div class="bp-av-cell"><span class="bp-av-participant-name">{{ $displayName }}</span></div>
    <div class="bp-av-cell"><span class="bp-av-participant-company">{{ $participant['company'] ?? '—' }}</span></div>
    <div class="bp-av-cell"><span class="bp-av-status-text {{ $statusClass }}">{{ $statusLabel }}</span></div>
    <div class="bp-av-cell bp-av-badge-cell" @click.stop>
        @if ($badgeUrl)
            <form method="POST" action="{{ $badgeUrl }}" target="bp-av-badge-download-frame">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm bp-av-row-btn">{{ __('Ausweis') }}</button>
            </form>
        @else
            <span class="bp-av-participant-status">—</span>
        @endif
    </div>
    <div class="bp-av-cell bp-av-action-cell" @click.stop>
        @if ($canCheckIn)
            <button
                type="button"
                wire:click="checkIn({{ (int) ($visitId ?? $participant['visit_id']) }}, {{ (int) $participant['visitor_id'] }})"
                wire:loading.attr="disabled"
                class="btn btn-primary btn-sm bp-av-row-btn"
            >
                {{ $checkInLabel }}
            </button>
        @elseif ($canCheckOut)
            <button
                type="button"
                wire:click="checkOut({{ (int) ($visitId ?? $participant['visit_id']) }}, {{ (int) $participant['visitor_id'] }})"
                wire:loading.attr="disabled"
                class="btn btn-primary btn-sm bp-av-row-btn"
            >
                {{ __('Check-out') }}
            </button>
        @else
            <span class="bp-av-participant-status">—</span>
        @endif
    </div>
</div>
