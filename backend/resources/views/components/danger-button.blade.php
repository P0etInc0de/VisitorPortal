<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-error text-white bp-btn-compact']) }}>
    {{ $slot }}
</button>
