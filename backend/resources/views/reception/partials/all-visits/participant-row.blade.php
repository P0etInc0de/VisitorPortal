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
@endphp

<div class="bp-av-participant-row">
    <div class="bp-av-cell"><span class="bp-av-participant-name">{{ $displayName }}</span></div>
    <div class="bp-av-cell"><span class="bp-av-participant-company">{{ $participant['company'] ?? '—' }}</span></div>
    <div class="bp-av-cell"><span class="bp-av-status-text {{ $statusClass }}">{{ $statusLabel }}</span></div>
    <div class="bp-av-cell bp-av-badge-cell">
        @if ($badgeUrl)
            <form method="POST" action="{{ $badgeUrl }}">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm bp-av-row-btn">{{ __('Ausweis') }}</button>
            </form>
        @else
            <button type="button" class="btn btn-outline btn-sm bp-av-row-btn">{{ __('Ausweis') }}</button>
        @endif
    </div>
    <div class="bp-av-cell bp-av-action-cell">
        @if ($canCheckIn && $checkInUrl)
            <form method="POST" action="{{ $checkInUrl }}" data-bp-ajax="participant-action" data-bp-visit-id="{{ $visitId ?? ($participant['visit_id'] ?? '') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm bp-av-row-btn">{{ $checkInLabel }}</button>
            </form>
        @elseif ($canCheckOut && $checkOutUrl)
            <form method="POST" action="{{ $checkOutUrl }}" data-bp-ajax="participant-action" data-bp-visit-id="{{ $visitId ?? ($participant['visit_id'] ?? '') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm bp-av-row-btn">{{ __('Check-out') }}</button>
            </form>
        @else
            <span class="bp-av-participant-status">—</span>
        @endif
    </div>
</div>
