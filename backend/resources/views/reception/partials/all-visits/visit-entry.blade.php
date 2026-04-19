@php
    $visitId = (int) ($visit['id'] ?? $index ?? 0);
    $detailId = $detailId ?? ('bp-av-details-visit-'.$visitId);
    $isExpanded = in_array($visitId, array_map('intval', $expandedVisitIds ?? []), true);
    $participants = $visit['participants'] ?? [];
    $noteText = trim((string) ($visit['note_text'] ?? ($visit['notes'] ?? '')));
    $visitStatusLabel = (string) ($visit['status_label'] ?? ($visit['status'] ?? ''));
    $visitStatusClass = trim((string) ($visit['status_class'] ?? ''));
    $visitStatusTextClass = $visitStatusClass !== '' ? $visitStatusClass : 'text-base-content/75';
    $visitDateLabel = $visit['date_time_label'] ?? null;
@endphp

<article
    class="bp-av-entry group {{ $isExpanded ? 'bp-av-entry-expanded' : '' }}"
    wire:key="visit-entry-{{ $visitId }}"
    x-data="{
        open: @js($isExpanded),
        openUrl: @js($visit['open_url'] ?? route('portal.visits.show', $visitId)),
        clickTimer: null,
        queueToggle() {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer);
            }

            this.clickTimer = window.setTimeout(() => {
                this.$dispatch('bp-av-capture-scroll');
                this.open = !this.open;
                this.$wire.toggleExpanded({{ $visitId }});
                this.clickTimer = null;
            }, 180);
        },
        openVisit() {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer);
                this.clickTimer = null;
            }

            window.location = this.openUrl;
        },
    }"
    x-bind:class="open ? 'bp-av-entry-expanded' : ''"
>
    <div
        class="grid min-h-[2.35rem] cursor-pointer grid-cols-[var(--bp-av-grid-columns)] items-center gap-[0.45rem] bg-[var(--bp-surface-strong)] px-3 py-[0.18rem] transition-colors duration-150 group-hover:bg-[rgba(31,42,68,0.03)]"
        x-bind:class="open ? 'bg-[rgba(31,42,68,0.025)]' : ''"
        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        @click="queueToggle()"
        @dblclick.stop.prevent="openVisit()"
    >
        <div class="flex min-w-0 items-center justify-center" @click.stop>
            <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" name="selected_visits[]" value="{{ $visitId }}" aria-label="{{ __('Besuch auswählen') }}">
        </div>

        <div class="flex min-w-0 items-center">
            <span class="truncate text-[0.9rem] font-bold leading-[1.18] text-[var(--bp-text)]">{{ $visit['title'] }}</span>
        </div>

        <div class="flex min-w-0 items-center">
            <span class="truncate text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]">{{ $visitDateLabel ?: '—' }}</span>
        </div>

        <div class="flex min-w-0 items-center">
            @if ($visitStatusLabel !== '')
                <span class="inline-flex max-w-full min-w-0 items-center justify-start truncate text-[0.8rem] font-bold leading-[1.22] {{ $visitStatusTextClass }}">{{ $visitStatusLabel }}</span>
            @else
                <span class="inline-flex max-w-full min-w-0 items-center justify-start truncate text-[0.8rem] font-bold leading-[1.22] text-base-content/75">—</span>
            @endif
        </div>

        <div class="flex min-w-0 items-center">
            <span class="truncate text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]">{{ $visit['host_display'] ?? $visit['host'] ?? '—' }}</span>
        </div>

        <div class="flex min-w-0 items-center justify-center">
            <span class="w-full text-center text-[0.82rem] font-semibold leading-[1.24] text-[rgba(31,42,68,0.82)]">{{ $visit['participants_count'] ?? count($participants) }}</span>
        </div>

        <div class="flex min-w-0 items-center">
            <span class="block max-w-full truncate text-[0.82rem] leading-[1.24] text-[rgba(31,42,68,0.72)]" title="{{ $noteText !== '' ? $noteText : '—' }}">{{ $noteText !== '' ? $noteText : '—' }}</span>
        </div>
    </div>

    <div id="{{ $detailId }}" class="bg-[rgba(31,42,68,0.02)] px-3 pb-[0.55rem] pl-[3.05rem]" x-show="open" x-cloak>
        <div class="overflow-hidden rounded-[1rem] border border-[rgba(31,42,68,0.08)] bg-[var(--bp-surface-strong)]">
            <div class="grid grid-cols-[var(--bp-av-participant-grid-columns)] items-center gap-[0.55rem] border-b border-[rgba(15,23,42,0.10)] bg-[rgba(31,42,68,0.04)] px-[0.65rem] py-[0.34rem] text-[0.7rem] font-bold uppercase tracking-[0.04em] text-[rgba(31,42,68,0.74)]">
                <div>{{ __('Name') }}</div>
                <div>{{ __('Unternehmen') }}</div>
                <div>{{ __('Status') }}</div>
                <div>{{ __('Ausweis') }}</div>
                <div>{{ __('Aktion') }}</div>
            </div>

            @if (count($participants) > 0)
                @foreach ($participants as $participant)
                    @include('reception.partials.all-visits.participant-row', [
                        'participant' => $participant,
                        'visitId' => $visitId,
                    ])
                @endforeach
            @else
                <div class="bp-av-empty">{{ __('Keine Teilnehmenden vorhanden.') }}</div>
            @endif
        </div>
    </div>
</article>
