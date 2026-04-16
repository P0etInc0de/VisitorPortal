<section class="bp-av-table-card" data-bp-fragment="visit-stack">
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
            </div>

            <div class="bp-av-table-body">
                <div id="bp-av-visit-stack">
                    @php
                        $previousYear = null;
                        $weekdayMap = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
                    @endphp

                    @forelse ($visits as $index => $visit)
                        @php
                            $scheduledFrom = null;
                            $yearLabel = null;

                            if (! empty($visit['scheduled_from'])) {
                                try {
                                    $scheduledFrom = \Illuminate\Support\Carbon::createFromFormat('d.m.Y H:i', $visit['scheduled_from']);
                                } catch (\Throwable $e) {
                                    $scheduledFrom = null;
                                }
                            }

                            if ($scheduledFrom) {
                                $yearLabel = $scheduledFrom->format('Y');
                            }

                            if ($yearLabel === null) {
                                $yearLabel = __('Ohne Jahr');
                            }
                        @endphp

                        @if ($yearLabel !== $previousYear)
                            <div class="bp-av-year-divider"><span>{{ $yearLabel }}</span></div>
                            @php $previousYear = $yearLabel; @endphp
                        @endif

                        @include('reception.partials.all-visits.visit-entry', [
                            'visit' => $visit,
                            'index' => $index,
                            'weekdayMap' => $weekdayMap,
                        ])
                    @empty
                        <div class="bp-av-empty">{{ __('Keine Besuche gefunden.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
