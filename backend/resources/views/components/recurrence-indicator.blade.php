@props([
    'modified' => false,
    'label' => null,
    'badge' => false,
])

@php
    $label = $label ?: ($badge ? __('Recurring') : ($modified ? __('Serientermin (manuell angepasst)') : __('Serientermin')));
@endphp

@if ($badge)
    <span
        {{ $attributes->class([
            'badge inline-flex items-center gap-1.5 rounded-full text-sm font-semibold',
            $modified ? 'badge-warning badge-outline' : 'badge-primary badge-outline',
        ]) }}
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        <span>{{ $label }}</span>
    </span>
@else
    <span
        {{ $attributes->class([
            'inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full',
            $modified ? 'text-warning' : 'text-base-content/45',
        ]) }}
        title="{{ $label }}"
        aria-label="{{ $label }}"
        role="img"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
        <span class="sr-only">{{ $label }}</span>
    </span>
@endif
