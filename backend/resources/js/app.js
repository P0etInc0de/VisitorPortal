import './bootstrap';

import focus from '@alpinejs/focus';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);
});

if (!window.__bpLivewireNavigateRejectionHandlerInstalled) {
    window.__bpLivewireNavigateRejectionHandlerInstalled = true;

    let navigationInProgress = false;
    let expectedCancellationUntil = 0;

    /**
     * Livewire wire:navigate can intentionally cancel an in-flight page fetch
     * when the user starts another navigation before the previous one finishes.
     *
     * In that case Livewire may expose an unhandled promise rejection shaped like:
     * { status: null, body: null, json: null, errors: null }
     *
     * This handler only suppresses that expected cancellation noise shortly after
     * a second navigation starts. Real server, validation, and network errors must
     * stay visible.
     */
    const looksLikeLivewireCanceledNavigateResponse = (reason) => {
        return reason
            && typeof reason === 'object'
            && reason.status === null
            && reason.body === null
            && reason.json === null
            && reason.errors === null;
    };

    const markNavigationStarted = () => {
        if (navigationInProgress) {
            expectedCancellationUntil = Date.now() + 2000;
        }
        navigationInProgress = true;
    };

    document.addEventListener('livewire:navigate', markNavigationStarted);

    document.addEventListener('livewire:navigated', () => {
        navigationInProgress = false;
    });

    window.addEventListener('unhandledrejection', (event) => {
        if (!looksLikeLivewireCanceledNavigateResponse(event.reason)) {
            return;
        }

        if (Date.now() > expectedCancellationUntil) {
            return;
        }

        event.preventDefault();

        if (import.meta.env.DEV) {
            console.debug('Suppressed expected canceled Livewire navigation request.', event.reason);
        }
    });
}
