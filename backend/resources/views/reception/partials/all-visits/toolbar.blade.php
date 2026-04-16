<div class="bp-av-toolbar" data-bp-fragment="toolbar">
    <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-search-form">
        <label class="bp-search bp-av-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="6" />
                <path d="M20 20l-3.5-3.5" />
            </svg>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                class="input input-bordered"
                placeholder="{{ __('Suche') }}"
            >
            <input type="hidden" name="range" value="{{ $activeRange ?: 'week' }}">
            @if ($dateFrom !== '')
                <input type="hidden" name="from" value="{{ $dateFrom }}">
            @endif
            @if ($dateTo !== '')
                <input type="hidden" name="to" value="{{ $dateTo }}">
            @endif
        </label>
    </form>

    <form method="GET" action="{{ route('reception.all-visits') }}" class="bp-av-date-form" data-bp-reset-url="{{ route('reception.all-visits', ['range' => 'all']) }}">
        <input type="hidden" name="q" value="{{ $search }}">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="from" value="{{ $dateFrom }}" class="input input-bordered input-sm">
        <span>{{ __('bis') }}</span>
        <input type="date" name="to" value="{{ $dateTo }}" class="input input-bordered input-sm">
        <button type="submit" class="btn btn-outline btn-sm">{{ __('Anwenden') }}</button>
        <a href="{{ route('reception.all-visits', ['range' => 'all']) }}" class="btn btn-outline btn-sm">{{ __('Zurücksetzen') }}</a>
    </form>
</div>
