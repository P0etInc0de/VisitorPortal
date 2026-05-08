import './bootstrap';

import focus from '@alpinejs/focus';
import { installRuntimeChannel } from './runtime/channel';

installRuntimeChannel();

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);

    window.Alpine.data('visitParticipantRow', (participant) => ({
        participant: participant,
        busy: false,

        async submitAction(url) {
            if (this.busy || !url) {
                return;
            }

            this.busy = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new URLSearchParams({
                        _token: csrfToken,
                    }).toString(),
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const data = await response.json();

                if (data.participant) {
                    this.participant = {
                        ...this.participant,
                        ...data.participant,
                    };
                }
            } catch (error) {
                window.location.reload();
            } finally {
                this.busy = false;
            }
        },
    }));
});

if (!window.__bpUserThemeResolverInstalled) {
    window.__bpUserThemeResolverInstalled = true;

    const systemDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

    const resolveSystemTheme = () => {
        return systemDarkQuery.matches ? 'dark' : 'light';
    };

    const resolveEffectiveTheme = (themePreference) => {
        if (themePreference === 'light') {
            return 'light';
        }

        if (themePreference === 'dark') {
            return 'dark';
        }

        if (themePreference === 'true-black') {
            return 'true-black';
        }

        return resolveSystemTheme();
    };

    const resolveThemeBackground = (effectiveTheme) => {
        if (effectiveTheme === 'true-black') {
            return '#000000';
        }

        if (effectiveTheme === 'dark') {
            return '#09090b';
        }

        return '#f3f5f9';
    };

    const writeEffectiveThemeCookie = () => {
        document.cookie = 'theme_effective=' + encodeURIComponent(resolveSystemTheme())
            + '; Path=/; Max-Age=31536000; SameSite=Lax';
    };

    const applyResolvedThemePreference = () => {
        const root = document.documentElement;
        const themePreference = root.dataset.themePreference || 'system';
        const effectiveTheme = resolveEffectiveTheme(themePreference);
        const backgroundColor = resolveThemeBackground(effectiveTheme);

        root.setAttribute('data-theme', effectiveTheme);
        root.classList.toggle('dark', effectiveTheme === 'dark' || effectiveTheme === 'true-black');
        root.style.colorScheme = effectiveTheme === 'light' ? 'light' : 'dark';
        root.style.setProperty('--bp-theme-background', backgroundColor);
        root.style.backgroundColor = backgroundColor;

        if (document.body) {
            document.body.style.backgroundColor = backgroundColor;
        }

        writeEffectiveThemeCookie();
    };

    applyResolvedThemePreference();

    document.addEventListener('livewire:navigate', () => {
        applyResolvedThemePreference();
    });

    document.addEventListener('livewire:navigating', () => {
        applyResolvedThemePreference();
    });

    document.addEventListener('livewire:navigated', () => {
        applyResolvedThemePreference();
    });

    window.addEventListener('pageshow', () => {
        applyResolvedThemePreference();
    });

    if (systemDarkQuery.addEventListener) {
        systemDarkQuery.addEventListener('change', applyResolvedThemePreference);
    } else {
        systemDarkQuery.addListener(applyResolvedThemePreference);
    }
}

if (!window.__bpSidebarScrollRestorationInstalled) {
    window.__bpSidebarScrollRestorationInstalled = true;

    let sidebarScrollPos;

    document.addEventListener('livewire:navigating', () => {
        sidebarScrollPos = document.querySelector('#sidebar-content')?.scrollTop;
    });

    document.addEventListener('livewire:navigated', () => {
        if (sidebarScrollPos === undefined) {
            return;
        }

        const sidebar = document.querySelector('#sidebar-content');

        if (sidebar) {
            sidebar.scrollTo({ top: sidebarScrollPos, behavior: 'instant' });
        }
    });
}

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
