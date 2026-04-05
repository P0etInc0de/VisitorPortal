<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline rounded-xl']) }}>
    {{ $slot }}
</button>
