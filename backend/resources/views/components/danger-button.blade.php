<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-error text-white']) }}>
    {{ $slot }}
</button>
