@props([
    'label' => null,
    'name' => null,
    'checked' => false,
])

<div class="form-control w-full">
    <label class="label cursor-pointer flex justify-start gap-4 py-2">
        {{-- Hidden Fallback Input --}}
        @if(is_string($name))
            <input type="hidden" name="{{ $name }}" value="0">
        @endif
        {{-- The Toggle Input --}}
        <input
            type="checkbox"
            value="1"
            @if(is_string($name)) name="{{ $name }}" @endif

        @if(is_string($name))
            @checked(old($name, $checked))
            @else
            @checked($checked)
            @endif

            {{ $attributes->merge([
                'class' => "toggle toggle-primary transition-all"
            ]) }}
        >

        @if($label)
            <span class="label-text text-sm font-medium text-base-content">
                {{ $label }}
            </span>
        @endif
    </label>

    @if(is_string($name))
        @error($name)
        <span class="mt-1 block text-sm text-red-500 font-medium">{{ $message }}</span>
        @enderror
    @endif
</div>
