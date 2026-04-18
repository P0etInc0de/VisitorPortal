@php
    $scheduledFrom = null;
    $visitDateLabel = null;
    $visitId = $visit['id'] ?? $index ?? null;
    $detailId = $detailId ?? ('bp-av-details-visit-'.$visitId);
    $isExpanded = in_array((int) $visitId, array_map('intval', $expandedVisitIds ?? []), true);

    if (! empty($visit['scheduled_from'])) {
        try {
            $scheduledFrom = \Illuminate\Support\Carbon::createFromFormat('d.m.Y H:i', $visit['scheduled_from']);
        } catch (\Throwable $e) {
            $scheduledFrom = null;
        }
    }

    if ($scheduledFrom) {
        $visitDateLabel = ($weekdayMap[$scheduledFrom->dayOfWeekIso] ?? $scheduledFrom->isoFormat('dd')).', '.$scheduledFrom->format('d.m.').' | '.$scheduledFrom->format('H:i').' '.__('Uhr');
    } elseif (! empty($visit['date']) && ! empty($visit['time'])) {
        $visitDateLabel = $visit['date'].' | '.$visit['time'].' '.__('Uhr');
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
    $noteText = trim((string) ($visit['notes'] ?? ($visit['note'] ?? '')));
@endphp

<article
    class="bp-av-entry {{ $isExpanded ? 'bp-av-entry-expanded' : '' }}"
    wire:key="visit-entry-{{ $visitId }}"
    x-data="{ open: @js($isExpanded), openUrl: @js($visit['open_url'] ?? route('portal.visits.show', $visit['id'])) }"
    x-bind:class="open ? 'bp-av-entry-expanded' : ''"
>
    <div
        class="bp-av-visit-row"
        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        @click="$wire.toggleExpanded({{ (int) $visitId }}); open = !open"
        @dblclick.stop="window.location = openUrl"
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
