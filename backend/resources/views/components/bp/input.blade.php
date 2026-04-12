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
        {{-- Nur wenn $name ein String ist, setzen wir das HTML-Attribut name --}}
        @if(is_string($name)) name="{{ $name }}" @endif

        {{-- WICHTIG: Nur old() aufrufen, wenn $name existiert.
             Sonst nehmen wir $value (falls es kein Array ist) oder einen leeren String. --}}
        value="{{ is_string($name) ? old($name, $value) : (!is_array($value) ? $value : '') }}"

        {{ $attributes->merge([
            'class' => "input input-bordered h-12 w-full rounded-xl border-slate-300 bg-white focus:border-indigo-500 transition-all"
        ]) }}
    >

    @if(is_string($name))
        @error($name)
        <span class="mt-1 block text-sm text-red-500 font-medium">{{ $message }}</span>
        @enderror
    @endif
</div>
