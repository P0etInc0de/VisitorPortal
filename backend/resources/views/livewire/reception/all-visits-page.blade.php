<div
    x-data="{
        interactionToken: @entangle('interactionToken'),
        tableBodyScrollTop: 0,
        tableWrapScrollLeft: 0,
        captureScroll() {
            this.tableBodyScrollTop = this.$refs.tableBody ? this.$refs.tableBody.scrollTop : 0;
            this.tableWrapScrollLeft = this.$refs.tableWrap ? this.$refs.tableWrap.scrollLeft : 0;
        },
        restoreScroll() {
            this.$nextTick(() => {
                if (this.$refs.tableBody) {
                    this.$refs.tableBody.scrollTop = this.tableBodyScrollTop;
                }

                if (this.$refs.tableWrap) {
                    this.$refs.tableWrap.scrollLeft = this.tableWrapScrollLeft;
                }
            });
        },
    }"
    x-on:av-capture-scroll.window="captureScroll()"
    x-effect="interactionToken; restoreScroll()"
>
    <div class="grid min-w-0 grid-rows-[auto_auto_auto_minmax(0,1fr)] content-start gap-[0.45rem] min-h-[calc(100dvh-7.35rem)] max-h-[calc(100dvh-7.35rem)] overflow-visible max-[1100px]:min-h-auto max-[1100px]:max-h-none">
        @include('reception.partials.all-visits.toolbar')

        @include('reception.partials.all-visits.filter-bar', [
            'ranges' => $ranges,
            'activeRange' => $activeRange,
        ])

        <div class="px-0.5 text-[0.84rem] leading-[1.25] text-base-content/65">
            <span><strong class="font-semibold text-base-content/75">{{ __('Einmal klicken') }}</strong> {{ __('→ Besucher-Details') }} &nbsp;·&nbsp; <strong class="font-semibold text-base-content/75">{{ __('Doppelt klicken') }}</strong> {{ __('→ Besuch öffnen') }}</span>
        </div>

        @include('reception.partials.all-visits.visit-stack', [
            'visits' => $visits,
            'expandedVisitIds' => $expandedVisitIds,
            'showParticipantControls' => $showParticipantControls ?? true,
        ])

        @if ($showParticipantControls ?? true)
            <iframe name="av-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
        @endif
    </div>
</div>
