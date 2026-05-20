@props(['disabled' => false])

<x-text-input
    data-totp-code-input
    @disabled($disabled)
    {{ $attributes->merge(['inputmode' => 'numeric', 'autocomplete' => 'one-time-code']) }}
/>

@once
    <script>
        document.addEventListener('input', (event) => {
            const input = event.target.closest('[data-totp-code-input]');

            if (! input || /\s/.test(input.value)) {
                return;
            }

            const digits = input.value.replace(/\s+/g, '');

            if (! /^\d{4,6}$/.test(digits)) {
                return;
            }

            const formatted = digits.length > 3
                ? `${digits.slice(0, 3)} ${digits.slice(3)}`
                : digits;

            if (formatted === input.value) {
                return;
            }

            const cursorWasAtEnd = input.selectionStart === input.value.length
                && input.selectionEnd === input.value.length;

            input.value = formatted;

            if (cursorWasAtEnd) {
                input.setSelectionRange(formatted.length, formatted.length);
            }
        });
    </script>
@endonce
