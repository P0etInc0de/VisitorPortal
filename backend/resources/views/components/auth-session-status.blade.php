@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-medium text-success']) }}>
        {{ $status }}
    </div>
@endif
