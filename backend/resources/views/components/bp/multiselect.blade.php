@props([
    'label' => null,
    'name' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.min.css" rel="stylesheet">

<div class="w-full">
    @if($label)
        <label class="form-control w-full" for="{{ $inputId }}">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">
                    {{ $label }}
                </span>
            </div>
        </label>
    @endif

    <select
        id="{{ $inputId }}"
        {{-- If $name exists, append [] for array submission --}}
        @if(is_string($name)) name="{{ $name }}[]" @endif
        multiple
        {{ $attributes }}
    >
        {{ $slot }}
    </select>

    @if(is_string($name))
        @error($name)
        <span class="mt-1 block text-sm text-red-500 font-medium">{{ $message }}</span>
        @enderror
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect("#tags",{
            plugins: ['remove_button'], // Adds a small 'x' to remove selections
            create: false,              // Set to true if you want users to be able to create new tags on the fly
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
</script>
