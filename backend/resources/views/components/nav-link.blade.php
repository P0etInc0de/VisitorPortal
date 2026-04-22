@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-primary px-1 pt-1 text-sm font-medium leading-5 text-base-content transition duration-150 ease-in-out focus:border-primary focus:outline-none'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-base-content/65 transition duration-150 ease-in-out hover:border-base-300 hover:text-base-content focus:border-base-300 focus:text-base-content focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
