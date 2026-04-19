<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline bp-btn-compact']) }}>
    {{ $slot }}
</button>
