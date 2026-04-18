@php
    $visitId = $visit['id'] ?? $index ?? null;
    $detailId = $detailId ?? ('bp-av-details-visit-'.$visitId);
    $isExpanded = in_array((int) $visitId, array_map('intval', $expandedVisitIds ?? []), true);
    $visitStatusLabel = (string) ($visit['status_label'] ?? ($visit['status']['label'] ?? ($visit['status'] ?? '')));
    $visitStatusClass = trim(str_replace('bp-status-pill', '', (string) ($visit['status_class'] ?? ($visit['status']['class'] ?? ''))));
    $participants = $visit['participants'] ?? array_merge($visit['visible_participants'] ?? [], $visit['hidden_participants'] ?? []);
    $noteText = (string) ($visit['note_text'] ?? trim((string) ($visit['notes'] ?? ($visit['note'] ?? ''))));
@endphp

<article
    class="bp-av-entry {{ $isExpanded ? 'bp-av-entry-expanded' : '' }}"
    wire:key="visit-entry-{{ $visitId }}"
    x-data="{
        open: @js($isExpanded),
        openUrl: @js($visit['open_url'] ?? route('portal.visits.show', $visit['id'])),
        clickTimer: null,
        queueToggle() {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer);
            }

            this.clickTimer = window.setTimeout(() => {
                this.$dispatch('bp-av-capture-scroll');
                this.open = !this.open;
                this.$wire.toggleExpanded({{ (int) $visitId }});
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
        class="bp-av-visit-row"
        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        @click="queueToggle()"
        @dblclick.stop.prevent="openVisit()"
    >
        <div class="bp-av-cell bp-av-cell-check" @click.stop>
            <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" name="selected_visits[]" value="{{ $visit['id'] ?? $index }}" aria-label="{{ __('Besuch auswählen') }}">
        </div>

        <div class="bp-av-cell">
            <div class="bp-av-title-wrap">
                <span class="bp-av-title">{{ $visit['title'] }}</span>
            </div>
        </div>

        <div class="bp-av-cell">
            <span class="bp-av-date-label">{{ $visit['date_time_label'] ?? '—' }}</span>
        </div>

        <div class="bp-av-cell">
            @if ($visitStatusLabel !== '')
                <span class="bp-av-status-text {{ $visitStatusClass }}">{{ $visitStatusLabel }}</span>
            @else
                <span class="bp-av-status-text">—</span>
            @endif
        </div>

        <div class="bp-av-cell">
            <span class="bp-av-host">{{ $visit['host_display'] ?? $visit['host'] ?? '—' }}</span>
        </div>

        <div class="bp-av-cell">
            <span class="bp-av-count">{{ $visit['participants_count'] ?? $visit['participant_count'] ?? count($participants) }}</span>
        </div>

        <div class="bp-av-cell">
            <span class="bp-av-note" title="{{ $noteText !== '' ? $noteText : '—' }}">{{ $noteText !== '' ? $noteText : '—' }}</span>
        </div>
    </div>

    <div id="{{ $detailId }}" class="bp-av-entry-details" x-show="open" x-cloak>
        <div class="bp-av-participant-table">
            <div class="bp-av-participant-head">
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
