<div class="bp-av-filter-bar">
    @foreach ($ranges as $value => $label)
        <button
            type="button"
            wire:click="setRange('{{ $value }}')"
            class="btn btn-sm {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
