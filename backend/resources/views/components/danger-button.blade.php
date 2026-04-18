<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-error bp-btn text-white']) }}>
    {{ $slot }}
</button>
