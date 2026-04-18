<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary bp-btn']) }}>
    {{ $slot }}
</button>
