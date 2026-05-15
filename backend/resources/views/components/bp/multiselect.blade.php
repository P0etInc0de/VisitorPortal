@props([
    'label' => null,
    'name' => null,
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

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
        data-bp-tom-select
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
