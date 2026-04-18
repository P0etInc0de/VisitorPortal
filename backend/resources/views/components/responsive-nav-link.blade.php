@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-l-4 border-primary bg-primary/10 py-2 ps-3 pe-4 text-start text-base font-medium text-primary transition duration-150 ease-in-out focus:bg-primary/15 focus:text-primary focus:outline-none'
            : 'block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-base-content/70 transition duration-150 ease-in-out hover:border-base-300 hover:bg-base-200/80 hover:text-base-content focus:border-base-300 focus:bg-base-200/80 focus:text-base-content focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
