<div>
    <div class="bp-content bp-av-page">
        @include('reception.partials.all-visits.toolbar', [
            'search' => $search,
            'activeRange' => $activeRange,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])

        @include('reception.partials.all-visits.filter-bar', [
            'ranges' => $ranges,
            'search' => $search,
            'activeRange' => $activeRange,
            'rangeDateValues' => $rangeDateValues,
        ])

        <div class="bp-av-interaction-hint">
            <span><strong>{{ __('Einmal klicken') }}</strong> {{ __('→ Besucher-Details') }} &nbsp;·&nbsp; <strong>{{ __('Doppelt klicken') }}</strong> {{ __('→ Besuch öffnen') }}</span>
        </div>

        @include('reception.partials.all-visits.visit-stack', [
            'visits' => $visits,
            'expandedVisitIds' => $expandedVisitIds,
        ])

        <iframe name="bp-av-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
    </div>
</div>
