@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null
])

<div class="w-full">
    @if($label)
        <label class="form-control w-full">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">
                    {{ $label }}
                </span>
            </div>
        </label>
    @endif

    <input
        type="{{ $type }}"

        @if(is_string($name)) name="{{ $name }}" @endif

        value="{{ is_string($name) ? old($name, $value) : (!is_array($value) ? $value : '') }}"

        {{ $attributes->merge([
            'class' => "input input-bordered h-12 w-full rounded-xl border-base-300 bg-base-100 focus:border-primary transition-all"
        ]) }}
    >

    @if(is_string($name))
        @error($name)
        <span class="mt-1 block text-sm text-red-500 font-medium">{{ $message }}</span>
        @enderror
    @endif
</div>
