@php
    $salutation = trim((string) ($participant['salutation'] ?? ''));
    $title = trim((string) ($participant['title'] ?? ''));
    $baseName = trim((string) ($participant['name'] ?? '—'));
    $displayName = trim(collect([
        $salutation,
        $baseName !== '—' ? $baseName : null,
    ])->filter()->implode(' '));
    $displayName = $displayName !== '' ? $displayName : '—';

    $badgeUrl = $participant['badge_url'] ?? null;
    $canPrintBadge = (bool) ($participant['can_print_badge'] ?? false);
    $rawStatusLabel = $participant['status_label'] ?? ($participant['status']['label'] ?? ($participant['status'] ?? null));
    $statusLabel = $rawStatusLabel ? preg_replace('/^\s*[•·]\s*/u', '', (string) $rawStatusLabel) : '—';
    $statusClass = trim((string) ($participant['status_class'] ?? ($participant['status']['class'] ?? '')));
    $statusTextClass = $statusClass !== '' ? $statusClass : 'text-base-content/75';
    $checkInLabel = $participant['check_in_label'] ?? __('Check-in');
    $canCheckIn = (bool) ($participant['can_check_in'] ?? false);
    $canCheckOut = (bool) ($participant['can_check_out'] ?? false);
    $showParticipantControls = $showParticipantControls ?? true;
    $participantGridTemplateColumns = $participantGridTemplateColumns ?? ($showParticipantControls
        ? 'minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, 0.95fr) minmax(5.25rem, 0.8fr) minmax(9rem, 0.92fr)'
        : 'minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, 0.95fr)');
@endphp

<div
    class="grid items-center gap-[0.55rem] border-t border-base-200 px-[0.65rem] py-[0.34rem] first:border-t-0"
    style="grid-template-columns: {{ $participantGridTemplateColumns }};"
    wire:key="{{ $participant['row_key'] ?? ('participant-row-'.$visitId.'-'.($participant['visitor_id'] ?? uniqid('', true))) }}">
    <div class="flex min-w-0 items-center">
        <div class="flex min-w-0 items-center gap-1.5">
            @if ($title !== '')
                <span
                    class="shrink-0 rounded-full bg-base-200 px-2 py-0.5 text-[0.72rem] font-medium text-base-content/70">{{ $title }}</span>
            @endif

            <span class="truncate text-[0.82rem] text-base-content">{{ $displayName }}</span>
        </div>
    </div>

    <div class="flex min-w-0 items-center">
        <span
            class="truncate text-[0.82rem] leading-[1.24] text-base-content/70">{{ $participant['company'] ?? '—' }}</span>
    </div>

    <div class="flex min-w-0 items-center">
        <span
            class="inline-flex max-w-full min-w-0 items-center truncate text-[0.8rem] font-bold leading-[1.22] {{ $statusTextClass }}">{{ $statusLabel }}</span>
    </div>

    @if ($showParticipantControls)
        <div class="flex min-w-0 items-center">
            @if ($canPrintBadge && $badgeUrl)
                <form
                    method="POST"
                    action="{{ $badgeUrl }}"
                    target="av-badge-download-frame"
                    x-on:submit="$dispatch('av-capture-scroll')"
                >
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-outline btn-sm w-auto justify-center px-2 text-center"
                        data-testid="all-visits-participant-id-card-button"
                        x-on:click="$dispatch('av-capture-scroll'); $wire.printBadge({{ (int) ($participant['visit_id'] ?? $visitId) }}, {{ (int) $participant['visitor_id'] }})"
                    >{{ __('Ausweis') }}</button>
                </form>
            @else
                <span class="text-[0.82rem] leading-[1.24] text-base-content/70">—</span>
            @endif
        </div>

        <div class="flex min-w-0 items-center">
            @if ($canCheckIn)
                <button
                    type="button"
                    wire:click="checkIn({{ (int) ($participant['visit_id'] ?? $visitId) }}, {{ (int) $participant['visitor_id'] }})"
                    x-on:click="$dispatch('av-capture-scroll')"
                    class="btn btn-primary btn-sm min-w-[8.5rem] w-[8.5rem] max-w-[8.5rem] justify-center text-center"
                    wire:loading.attr="disabled"
                    wire:target="checkIn"
                >
                    {{ $checkInLabel }}
                </button>
            @elseif ($canCheckOut)
                <button
                    type="button"
                    wire:click="checkOut({{ (int) ($participant['visit_id'] ?? $visitId) }}, {{ (int) $participant['visitor_id'] }})"
                    x-on:click="$dispatch('av-capture-scroll')"
                    class="btn btn-primary btn-sm min-w-[8.5rem] w-[8.5rem] max-w-[8.5rem] justify-center text-center"
                    wire:loading.attr="disabled"
                    wire:target="checkOut"
                >
                    {{ __('Check-out') }}
                </button>
            @else
                <span class="text-[0.82rem] leading-[1.24] text-base-content/70">—</span>
            @endif
        </div>
    @endif
</div>
