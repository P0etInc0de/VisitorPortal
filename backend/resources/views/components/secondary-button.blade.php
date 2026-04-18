<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline bp-btn']) }}>
    {{ $slot }}
</button>
