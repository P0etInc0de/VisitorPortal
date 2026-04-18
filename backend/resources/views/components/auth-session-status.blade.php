@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'bp-inline-status text-sm font-medium']) }}>
        {{ $status }}
    </div>
@endif
