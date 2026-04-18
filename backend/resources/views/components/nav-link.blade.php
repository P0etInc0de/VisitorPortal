@props(['active'])

@php
    $classes = ($active ?? false)
        ? 'inline-flex items-center border-b-2 border-primary px-1 pt-1 text-sm font-medium leading-5 text-base-content transition duration-150 ease-in-out focus:outline-none focus:border-primary'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-base-content/65 transition duration-150 ease-in-out hover:border-base-300 hover:text-base-content focus:outline-none focus:border-base-300 focus:text-base-content';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
