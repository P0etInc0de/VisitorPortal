<div class="grid items-center gap-[0.65rem] min-[1400px]:grid-cols-[minmax(17rem,1.18fr)_auto] max-[1100px]:grid-cols-1">
    <form class="min-w-0" wire:submit.prevent>
        <label class="relative block">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="pointer-events-none absolute left-[0.95rem] top-1/2 z-1 h-4 w-4 -translate-y-1/2 text-base-content/55">
                <circle cx="11" cy="11" r="6" />
                <path d="M20 20l-3.5-3.5" />
            </svg>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                class="input input-bordered input-sm w-full pl-10"
                placeholder="{{ __('Suche') }}"
            >
        </label>
    </form>

    <form
        wire:submit.prevent="applyCustomRange"
        x-on:submit="$dispatch('av-capture-scroll')"
        class="flex min-w-max flex-nowrap items-center gap-2 whitespace-nowrap max-[1100px]:flex-wrap"
    >
        <input type="date" wire:model.defer="dateFrom" class="input input-bordered input-sm basis-30 w-30 min-w-30 max-w-30">
        <span>{{ __('bis') }}</span>
        <input type="date" wire:model.defer="dateTo" class="input input-bordered input-sm basis-30 w-30 min-w-30 max-w-30">
        <button type="submit" class="btn btn-outline btn-sm" wire:loading.attr="disabled" wire:target="applyCustomRange">{{ __('Anwenden') }}</button>
        <button type="button" wire:click="resetFilters" x-on:click="$dispatch('av-capture-scroll')" class="btn btn-outline btn-sm" wire:loading.attr="disabled" wire:target="resetFilters">{{ __('Zurücksetzen') }}</button>
    </form>
</div>
