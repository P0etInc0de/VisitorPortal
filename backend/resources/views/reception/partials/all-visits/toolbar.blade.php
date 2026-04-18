<div class="bp-av-toolbar">
    <form class="bp-av-search-form" wire:submit.prevent>
        <label class="bp-search bp-av-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="6" />
                <path d="M20 20l-3.5-3.5" />
            </svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                class="input input-bordered"
                placeholder="{{ __('Suche') }}"
            >
        </label>
    </form>

    <form wire:submit.prevent="applyCustomRange" x-on:submit="$dispatch('bp-av-capture-scroll')" class="bp-av-date-form">
        <input type="date" wire:model.defer="dateFrom" class="input input-bordered input-sm">
        <span>{{ __('bis') }}</span>
        <input type="date" wire:model.defer="dateTo" class="input input-bordered input-sm">
        <button type="submit" class="btn btn-outline btn-sm" wire:loading.attr="disabled" wire:target="applyCustomRange">{{ __('Anwenden') }}</button>
        <button type="button" wire:click="resetFilters" x-on:click="$dispatch('bp-av-capture-scroll')" class="btn btn-outline btn-sm" wire:loading.attr="disabled" wire:target="resetFilters">{{ __('Zurücksetzen') }}</button>
    </form>
</div>
