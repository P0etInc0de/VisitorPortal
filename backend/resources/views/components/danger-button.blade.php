<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-error rounded-xl text-white']) }}>
    {{ $slot }}
</button>
