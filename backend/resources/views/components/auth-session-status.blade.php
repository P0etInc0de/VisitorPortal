@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-success']) }}>
        {{ $status }}
    </div>
@endif
