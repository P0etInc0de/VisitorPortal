<div class="bp-av-filter-bar" data-bp-fragment="filter-bar">
    @foreach ($ranges as $value => $label)
        @php
            $rangeParams = [
                'range' => $value,
                'q' => $search ?: null,
            ];

            if ($value !== 'all') {
                $rangeParams['from'] = $rangeDateValues[$value]['from'] ?? null;
                $rangeParams['to'] = $rangeDateValues[$value]['to'] ?? null;
            }
        @endphp
        <a href="{{ route('reception.all-visits', array_filter($rangeParams, fn ($rangeValue) => $rangeValue !== null && $rangeValue !== '')) }}" class="btn btn-sm {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
    @endforeach
</div>
