@php
    $salutation = trim((string) ($participant['salutation'] ?? ''));
    $title = trim((string) ($participant['title'] ?? ''));
    $baseName = trim((string) ($participant['name'] ?? '—'));
    $displayName = trim(collect([$salutation, $title, $baseName])->filter()->implode(' '));
    $displayName = $displayName !== '' ? $displayName : '—';

    $badgeUrl = $participant['badge_url'] ?? null;
    $rawStatusLabel = $participant['status_label'] ?? ($participant['status']['label'] ?? ($participant['status'] ?? null));
    $statusLabel = $rawStatusLabel ? preg_replace('/^\s*[•·]\s*/u', '', (string) $rawStatusLabel) : '—';
    $statusClass = trim((string) ($participant['status_class'] ?? ($participant['status']['class'] ?? '')));
    $statusTextClass = $statusClass !== '' ? $statusClass : 'text-base-content/75';
    $checkInLabel = $participant['check_in_label'] ?? __('Check-in');
    $canCheckIn = (bool) ($participant['can_check_in'] ?? false);
    $canCheckOut = (bool) ($participant['can_check_out'] ?? false);
@endphp

<div class="grid grid-cols-[var(--bp-av-participant-grid-columns)] items-center gap-[0.55rem] border-t border-[rgba(31,42,68,0.06)] px-[0.65rem] py-[0.34rem] first:border-t-0" wire:key="{{ $participant['row_key'] ?? ('participant-row-'.$visitId.'-'.($participant['visitor_id'] ?? uniqid())) }}">
    <div class="flex min-w-0 items-center">
        <span class="truncate text-[0.82rem] text-[var(--bp-text)]">{{ $displayName }}</span>
    </div>

    <div class="flex min-w-0 items-center">
        <span class="truncate text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]">{{ $participant['company'] ?? '—' }}</span>
    </div>

    <div class="flex min-w-0 items-center">
        <span class="inline-flex max-w-full min-w-0 items-center truncate text-[0.8rem] font-bold leading-[1.22] {{ $statusTextClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="flex min-w-0 items-center">
        @if ($badgeUrl)
            <form
                method="POST"
                action="{{ $badgeUrl }}"
                target="bp-av-badge-download-frame"
                x-on:submit="$dispatch('bp-av-capture-scroll')"
            >
                @csrf
                <button type="submit" class="btn btn-outline btn-sm bp-av-row-btn px-2">{{ __('Ausweis') }}</button>
            </form>
        @else
            <span class="text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]">—</span>
        @endif
    </div>

    <div class="flex min-w-0 items-center">
        @if ($canCheckIn)
            <button
                type="button"
                wire:click="checkIn({{ (int) ($participant['visit_id'] ?? $visitId) }}, {{ (int) $participant['visitor_id'] }})"
                x-on:click="$dispatch('bp-av-capture-scroll')"
                class="btn btn-primary btn-sm bp-av-row-btn bp-av-action-btn"
                wire:loading.attr="disabled"
                wire:target="checkIn"
            >
                {{ $checkInLabel }}
            </button>
        @elseif ($canCheckOut)
            <button
                type="button"
                wire:click="checkOut({{ (int) ($participant['visit_id'] ?? $visitId) }}, {{ (int) $participant['visitor_id'] }})"
                x-on:click="$dispatch('bp-av-capture-scroll')"
                class="btn btn-primary btn-sm bp-av-row-btn bp-av-action-btn"
                wire:loading.attr="disabled"
                wire:target="checkOut"
            >
                {{ __('Check-out') }}
            </button>
        @else
            <span class="text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]">—</span>
        @endif
    </div>
</div>
