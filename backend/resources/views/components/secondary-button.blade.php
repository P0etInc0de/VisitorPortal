<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline']) }}>
    {{ $slot }}
</button>
