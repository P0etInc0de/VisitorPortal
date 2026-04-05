<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary rounded-xl']) }}>
    {{ $slot }}
</button>
