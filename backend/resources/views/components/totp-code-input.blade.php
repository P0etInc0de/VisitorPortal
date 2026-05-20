@props(['disabled' => false])

<input
    data-totp-code-input
    @disabled($disabled)
    {{ $attributes->merge([
        'type' => 'text',
        'name' => 'code',
        'inputmode' => 'numeric',
        'autocomplete' => 'one-time-code',
        'class' => 'mt-1 block h-12 w-full rounded-xl border border-base-300 bg-base-100 px-4 text-base-content shadow-sm placeholder:text-base-content/40 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
    ]) }}
>

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
