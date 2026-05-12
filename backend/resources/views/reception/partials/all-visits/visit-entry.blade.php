@php
    $visitId = (int) ($visit['id'] ?? $index ?? 0);
    $detailId = $detailId ?? ('av-details-visit-'.$visitId);
    $isExpanded = in_array($visitId, array_map('intval', $expandedVisitIds ?? []), true);
    $participants = $visit['participants'] ?? [];
    $noteText = trim((string) ($visit['note_text'] ?? ($visit['notes'] ?? '')));
    $visitStatusLabel = (string) ($visit['status_label'] ?? ($visit['status'] ?? ''));
    $visitStatusClass = trim((string) ($visit['status_class'] ?? ''));
    $visitStatusTextClass = $visitStatusClass !== '' ? $visitStatusClass : 'text-base-content/75';
    $visitDateLabel = $visit['date_time_label'] ?? null;
    $showParticipantControls = $showParticipantControls ?? true;
    $visitGridColumns = $visitGridColumns ?? 'grid-cols-[1.85rem_minmax(11rem,1.28fr)_minmax(10.4rem,1.02fr)_minmax(6.75rem,0.7fr)_minmax(5.8rem,0.62fr)_3rem_minmax(10rem,1.06fr)]';
    $participantGridTemplateColumns = $participantGridTemplateColumns ?? ($showParticipantControls
        ? 'minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, 0.95fr) minmax(5.25rem, 0.8fr) minmax(9rem, 0.92fr)'
        : 'minmax(14rem, 1.55fr) minmax(10rem, 1.1fr) minmax(7rem, 0.95fr)');
@endphp

<article
    class="group overflow-hidden border-b border-base-300 last:border-b-0"
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
                this.$dispatch('av-capture-scroll');
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
>
    <div
        class="grid min-h-[2.35rem] cursor-pointer {{ $visitGridColumns }} items-center gap-[0.45rem] bg-base-100 px-3 py-[0.18rem] transition-colors duration-150 group-hover:bg-base-200/50"
        x-bind:class="open ? 'bg-base-200/40' : ''"
        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        @click="queueToggle()"
        @dblclick.stop.prevent="openVisit()"
    >
        <div class="flex min-w-0 items-center justify-center" @click.stop>
            <input type="checkbox" class="checkbox checkbox-sm h-4 w-4 rounded-[0.35rem]" name="selected_visits[]" value="{{ $visitId }}" aria-label="{{ __('Besuch auswählen') }}">
        </div>

        <div class="flex min-w-0 items-center gap-1.5">
            <span class="min-w-0 truncate text-[0.9rem] font-bold leading-[1.18] text-base-content">{{ $visit['title'] }}</span>
            @if ($visit['is_recurring'] ?? false)
                <x-recurrence-indicator :modified="(bool) ($visit['recurrence_is_modified'] ?? false)" />
            @endif
        </div>

        <div class="flex min-w-0 items-center">
            <span class="truncate text-[0.82rem] leading-[1.24] text-base-content/70">{{ $visitDateLabel ?: '—' }}</span>
        </div>

        <div class="flex min-w-0 items-center">
            @if ($visitStatusLabel !== '')
                <span class="inline-flex max-w-full min-w-0 items-center justify-start truncate text-[0.8rem] font-bold leading-[1.22] {{ $visitStatusTextClass }}">{{ $visitStatusLabel }}</span>
            @else
                <span class="inline-flex max-w-full min-w-0 items-center justify-start truncate text-[0.8rem] font-bold leading-[1.22] text-base-content/75">—</span>
            @endif
        </div>

        <div class="flex min-w-0 items-center">
            <span class="truncate text-[0.82rem] leading-[1.24] text-base-content/70">{{ $visit['host_display'] ?? $visit['host'] ?? '—' }}</span>
        </div>

        <div class="flex min-w-0 items-center justify-center">
            <span class="w-full text-center text-[0.82rem] font-semibold leading-[1.24] text-base-content/80">{{ $visit['participants_count'] ?? count($participants) }}</span>
        </div>

        <div class="flex min-w-0 items-center">
            <span class="block max-w-full truncate text-[0.82rem] leading-[1.24] text-base-content/70" title="{{ $noteText !== '' ? $noteText : '—' }}">{{ $noteText !== '' ? $noteText : '—' }}</span>
        </div>
    </div>

    <div id="{{ $detailId }}" class="bg-base-200/30 px-3 pb-[0.55rem] pl-[3.05rem]" x-show="open" x-cloak>
        <div class="overflow-hidden rounded-[1rem] border border-base-300 bg-base-100">
            <div class="grid items-center gap-[0.55rem] border-b border-base-300 bg-base-200/60 px-[0.65rem] py-[0.34rem] text-[0.7rem] font-bold uppercase tracking-[0.04em] text-base-content/75" style="grid-template-columns: {{ $participantGridTemplateColumns }};">
                <div>{{ __('Name') }}</div>
                <div>{{ __('Unternehmen') }}</div>
                <div>{{ __('Status') }}</div>
                @if ($showParticipantControls)
                    <div>{{ __('Ausweis') }}</div>
                    <div>{{ __('Aktion') }}</div>
                @endif
            </div>

            @if (count($participants) > 0)
                @foreach ($participants as $participant)
                    @include('reception.partials.all-visits.participant-row', [
                        'participant' => $participant,
                        'visitId' => $visitId,
                        'participantGridTemplateColumns' => $participantGridTemplateColumns,
                        'showParticipantControls' => $showParticipantControls,
                    ])
                @endforeach
            @else
                <div class="px-3 py-3 text-[0.88rem] text-base-content/70">{{ __('Keine Teilnehmenden vorhanden.') }}</div>
            @endif
        </div>
    </div>
</article>
