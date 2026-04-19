<section class="flex h-full min-h-0 flex-col self-stretch overflow-hidden rounded-[1.35rem] border border-base-300 bg-base-100 shadow-sm">
    <div class="h-full min-h-0 overflow-x-auto overflow-y-hidden" x-ref="tableWrap">
        <div class="flex h-full min-h-full min-w-[52rem] w-full flex-col">
            <div class="grid grid-cols-[var(--bp-av-grid-columns)] items-center gap-[0.45rem] border-b border-base-300 bg-base-100 px-3 py-[0.44rem] text-[0.76rem] font-bold uppercase tracking-[0.04em] text-base-content/65">
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
                            <div class="px-3 pt-1 pb-0.5 text-sm font-semibold text-base-content"><span>{{ $yearLabel }}</span></div>
                            @php $previousYear = $yearLabel; @endphp
                        @endif

                        @include('reception.partials.all-visits.visit-entry', [
                            'visit' => $visit,
                            'index' => $index,
                            'expandedVisitIds' => $expandedVisitIds ?? [],
                        ])
                    @empty
                        <div class="px-3 py-3 text-[0.88rem] text-base-content/70">{{ __('Keine Besuche gefunden.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
