@props([
    'label' => null,
    'name' => null,
    'type' => 'file',
    'value' => null,
    'src' => '',
    'id',
    'form_id'
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
    <label for="{{$id}}" class="btn w-full mb-3">{{__('neues Bild wählen')}}</label>
    <input
        id="{{$id}}"
        style="display:none"
        type="{{ $type }}"

        @if(is_string($name)) name="{{ $name }}" @endif

        value="{{ is_string($name) ? old($name, $value) : (!is_array($value) ? $value : '') }}"

        {{ $attributes->merge([
            'class' => "w-full btn-primary"
        ]) }}
    >
    @if(is_string($name))
        @error($name)
        <span class="mt-1 block text-sm text-red-500 font-medium">{{ $message }}</span>
        @enderror
    @endif

</div>
@if (!empty($src) && Storage::disk('public')->exists($src))
    <button type="submit" class="btn w-full mb-3" value="1" name="deleteImage">
        {{__('Bild entfernen')}}
    </button>
    <img src="{{ asset('storage/'.$src) }}" alt="{{__('Kein Bild ausgewählt')}}"/>
@endif

<script>
    document.getElementById("{{$id}}").addEventListener('change', function () {
        document.getElementById("{{$form_id}}").submit();
    });
</script>
