<div class="bp-av-filter-bar">
    @foreach ($ranges as $value => $label)
        <button
            type="button"
            wire:click="setRange('{{ $value }}')"
            x-on:click="$dispatch('bp-av-capture-scroll')"
            class="btn btn-sm {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}"
            wire:loading.attr="disabled"
            wire:target="setRange"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
