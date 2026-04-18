<section class="bp-av-table-card">
    <div class="bp-av-table-wrap" x-ref="tableWrap">
        <div class="bp-av-table">
            <div class="bp-av-table-head">
                <div class="bp-av-cell bp-av-cell-check">
                    <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" aria-label="{{ __('Alle Besuche auswählen') }}">
                </div>
                <div class="bp-av-cell">{{ __('Termin') }}</div>
                <div class="bp-av-cell">{{ __('Datum / Zeit') }}</div>
                <div class="bp-av-cell">{{ __('Status') }}</div>
                <div class="bp-av-cell">{{ __('Host') }}</div>
                <div class="bp-av-cell">{{ __('Teiln.') }}</div>
                <div class="bp-av-cell">{{ __('Notiz') }}</div>
            </div>

            <div class="bp-av-table-body" x-ref="tableBody">
                <div id="bp-av-visit-stack">
                    @php
                        $previousYear = null;
                    @endphp

                    @forelse ($visits as $index => $visit)
                        @php
                            $yearLabel = $visit['year_label'] ?? __('Ohne Jahr');
                        @endphp

                        @if ($yearLabel !== $previousYear)
                            <div class="bp-av-year-divider"><span>{{ $yearLabel }}</span></div>
                            @php $previousYear = $yearLabel; @endphp
                        @endif

                        @include('reception.partials.all-visits.visit-entry', [
                            'visit' => $visit,
                            'index' => $index,
                            'expandedVisitIds' => $expandedVisitIds ?? [],
                        ])
                    @empty
                        <div class="bp-av-empty">{{ __('Keine Besuche gefunden.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
