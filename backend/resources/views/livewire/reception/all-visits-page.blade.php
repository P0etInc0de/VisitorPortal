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
    x-on:bp-av-capture-scroll.window="captureScroll()"
    x-effect="interactionToken; restoreScroll()"
>
    <div class="min-w-0 bp-av-page">
        @include('reception.partials.all-visits.toolbar')

        @include('reception.partials.all-visits.filter-bar', [
            'ranges' => $ranges,
            'activeRange' => $activeRange,
        ])

        <div class="px-0.5 text-[0.84rem] leading-[1.25] text-[rgba(31,42,68,0.62)]">
            <span><strong class="font-semibold text-[rgba(31,42,68,0.74)]">{{ __('Einmal klicken') }}</strong> {{ __('→ Besucher-Details') }} &nbsp;·&nbsp; <strong class="font-semibold text-[rgba(31,42,68,0.74)]">{{ __('Doppelt klicken') }}</strong> {{ __('→ Besuch öffnen') }}</span>
        </div>

        @include('reception.partials.all-visits.visit-stack', [
            'visits' => $visits,
            'expandedVisitIds' => $expandedVisitIds,
        ])

        <iframe name="bp-av-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
    </div>
</div>
