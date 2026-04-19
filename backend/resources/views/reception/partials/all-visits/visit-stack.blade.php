<section class="flex h-full min-h-0 flex-col self-stretch overflow-hidden rounded-[1.35rem] border border-[var(--bp-border)] bg-[var(--bp-surface-strong)] shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
    <div class="h-full min-h-0 overflow-x-auto overflow-y-hidden" x-ref="tableWrap">
        <div class="flex h-full min-h-full min-w-[52rem] w-full flex-col">
            <div class="grid grid-cols-[var(--bp-av-grid-columns)] items-center gap-[0.45rem] border-b border-[var(--bp-border)] bg-[var(--bp-surface-strong)] px-3 py-[0.44rem] text-[0.76rem] font-bold uppercase tracking-[0.04em] text-[rgba(31,42,68,0.66)]">
                <div class="flex min-w-0 items-center justify-center">
                    <input type="checkbox" class="checkbox checkbox-sm bp-av-checkbox" aria-label="{{ __('Alle Besuche auswählen') }}">
                </div>
                <div class="flex min-w-0 items-center">{{ __('Termin') }}</div>
                <div class="flex min-w-0 items-center">{{ __('Datum / Zeit') }}</div>
                <div class="flex min-w-0 items-center">{{ __('Status') }}</div>
                <div class="flex min-w-0 items-center">{{ __('Host') }}</div>
                <div class="flex min-w-0 items-center justify-center">{{ __('Teiln.') }}</div>
                <div class="flex min-w-0 items-center">{{ __('Notiz') }}</div>
            </div>

            <div class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto" x-ref="tableBody">
                <div id="bp-av-visit-stack">
                    @php $previousYear = null; @endphp

                    @forelse ($visits as $index => $visit)
                        @php $yearLabel = $visit['year_label'] ?? __('Ohne Jahr'); @endphp

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
