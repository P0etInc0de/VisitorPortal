<script>
    (function () {
        const preference = @js($themePreference);
        const syncUrl = @js($themeSyncUrl);
        const trueBlackLabel = @js($trueBlackLabel);
        const root = document.documentElement;
        const appThemeStorageKey = 'app-theme-preference';
        const filamentThemeStorageKey = 'theme';
        const activeButtonIndex = { light: 0, dark: 1, system: 2 };
        const supportedPreferences = ['light', 'dark', 'system', 'true-black'];
        const supportedFilamentThemes = ['light', 'dark', 'system'];
        const prefersDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

        let lastNativeThemeSwitcherClickAt = 0;

        const normalizePreference = (themePreference) => {
            return supportedPreferences.includes(themePreference) ? themePreference : 'system';
        };

        const normalizeFilamentTheme = (theme) => {
            return supportedFilamentThemes.includes(theme) ? theme : null;
        };

        const systemTheme = () => {
            return prefersDarkQuery.matches ? 'dark' : 'light';
        };

        const writeEffectiveThemeCookie = () => {
            document.cookie = 'theme_effective=' + encodeURIComponent(systemTheme())
                + '; Path=/; Max-Age=31536000; SameSite=Lax';
        };

        const themeFromFilamentEvent = (event) => {
            if (typeof event.detail === 'string') {
                return normalizeFilamentTheme(event.detail);
            }

            if (event.detail && typeof event.detail === 'object') {
                return normalizeFilamentTheme(event.detail.theme)
                    ?? normalizeFilamentTheme(event.detail.value)
                    ?? normalizeFilamentTheme(event.detail.mode);
            }

            return normalizeFilamentTheme(localStorage.getItem(filamentThemeStorageKey));
        };

        const syncThemeSwitcherUi = (themePreference) => {
            const normalizedPreference = normalizePreference(themePreference);

            document.querySelectorAll('.fi-theme-switcher').forEach((switcher) => {
                const buttons = switcher.querySelectorAll('.fi-theme-switcher-btn');

                buttons.forEach((button) => button.classList.remove('fi-active'));

                if (normalizedPreference === 'true-black') {
                    switcher.querySelector('[data-app-theme="true-black"]')?.classList.add('fi-active');

                    return;
                }

                const index = activeButtonIndex[normalizedPreference];

                if (index !== undefined) {
                    buttons[index]?.classList.add('fi-active');
                }
            });
        };

        const applyThemePreference = (themePreference) => {
            const normalizedPreference = normalizePreference(themePreference);

            localStorage.setItem(appThemeStorageKey, normalizedPreference);
            root.dataset.themePreference = normalizedPreference;

            if (normalizedPreference === 'true-black') {
                root.setAttribute('data-theme', 'true-black');
                root.classList.add('dark');
                root.style.colorScheme = 'dark';
                localStorage.setItem(filamentThemeStorageKey, 'dark');
                writeEffectiveThemeCookie();
                syncThemeSwitcherUi('true-black');

                return;
            }

            if (normalizedPreference === 'dark') {
                root.setAttribute('data-theme', 'dark');
                root.classList.add('dark');
                root.style.colorScheme = 'dark';
                localStorage.setItem(filamentThemeStorageKey, 'dark');
                writeEffectiveThemeCookie();
                syncThemeSwitcherUi('dark');

                return;
            }

            if (normalizedPreference === 'light') {
                root.setAttribute('data-theme', 'light');
                root.classList.remove('dark');
                root.style.colorScheme = 'light';
                localStorage.setItem(filamentThemeStorageKey, 'light');
                writeEffectiveThemeCookie();
                syncThemeSwitcherUi('light');

                return;
            }

            const resolvedSystemTheme = systemTheme();

            root.setAttribute('data-theme', resolvedSystemTheme);
            root.classList.toggle('dark', resolvedSystemTheme === 'dark');
            root.style.colorScheme = resolvedSystemTheme === 'dark' ? 'dark' : 'light';
            localStorage.setItem(filamentThemeStorageKey, 'system');
            writeEffectiveThemeCookie();
            syncThemeSwitcherUi('system');
        };

        const persistThemePreference = async (themePreference) => {
            try {
                await fetch(syncUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ theme_preference: normalizePreference(themePreference) }),
                });
            } catch (error) {
                console.error('Unable to persist theme preference.', error);
            }
        };

        const applyAndPersistThemePreference = async (themePreference) => {
            const normalizedPreference = normalizePreference(themePreference);

            applyThemePreference(normalizedPreference);
            await persistThemePreference(normalizedPreference);
        };

        const closeThemeSwitcherDropdown = (button) => {
            let currentElement = button.closest('[x-data]');

            while (currentElement) {
                try {
                    const alpineData = window.Alpine?.$data(currentElement);

                    if (
                        alpineData
                        && Object.prototype.hasOwnProperty.call(alpineData, 'open')
                    ) {
                        alpineData.open = false;
                        button.blur();

                        return;
                    }
                } catch (error) {
                    // Ignore Alpine lookup errors and continue with the fallback below.
                }

                currentElement = currentElement.parentElement?.closest?.('[x-data]');
            }

            document.dispatchEvent(new KeyboardEvent('keydown', {
                key: 'Escape',
                bubbles: true,
            }));

            button.blur();
        };

        const mountTrueBlackButtons = () => {
            document.querySelectorAll('.fi-theme-switcher').forEach((switcher) => {
                if (switcher.querySelector('[data-app-theme="true-black"]')) {
                    return;
                }

                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'fi-theme-switcher-btn';
                button.dataset.appTheme = 'true-black';
                button.setAttribute('aria-label', trueBlackLabel);
                button.title = trueBlackLabel;
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true" fill="none"><path d="M12 2.75l2.72 5.51 6.08.88-4.4 4.29 1.04 6.05L12 16.62 6.56 19.48l1.04-6.05-4.4-4.29 6.08-.88L12 2.75z" fill="#000" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>';

                button.addEventListener('click', async (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    await applyAndPersistThemePreference('true-black');
                    closeThemeSwitcherDropdown(button);
                });

                switcher.appendChild(button);
            });

            syncThemeSwitcherUi(normalizePreference(localStorage.getItem(appThemeStorageKey) ?? preference));
        };

        applyThemePreference(preference);

        document.addEventListener('click', (event) => {
            const button = event.target.closest?.('.fi-theme-switcher-btn');

            if (!button || button.dataset.appTheme) {
                return;
            }

            const switcher = button.closest('.fi-theme-switcher');

            if (!switcher) {
                return;
            }

            const nativeButtons = Array.from(
                switcher.querySelectorAll('.fi-theme-switcher-btn:not([data-app-theme])')
            );

            const selectedTheme = ['light', 'dark', 'system'][nativeButtons.indexOf(button)];

            if (!selectedTheme) {
                return;
            }

            const previousPreference = normalizePreference(
                root.dataset.themePreference
                ?? localStorage.getItem(appThemeStorageKey)
                ?? preference
            );

            lastNativeThemeSwitcherClickAt = Date.now();

            /*
               Filament only knows light/dark/system. True Black is represented as
               Filament dark internally. Therefore clicking the native dark button while
               True Black is active may not emit a theme-changed event. In that case the
               normal theme-changed handler never runs, so we apply the selected native
               theme as a fallback after Filament had a chance to handle the click.
            */
            window.setTimeout(async () => {
                const currentPreference = normalizePreference(
                    root.dataset.themePreference
                    ?? localStorage.getItem(appThemeStorageKey)
                    ?? preference
                );

                if (
                    previousPreference !== selectedTheme
                    && currentPreference === previousPreference
                ) {
                    await applyAndPersistThemePreference(selectedTheme);
                }
            }, 80);
        }, true);

        document.addEventListener('theme-changed', async (event) => {
            const selectedTheme = themeFromFilamentEvent(event) ?? 'system';
            const isManualNativeThemeClick = Date.now() - lastNativeThemeSwitcherClickAt < 1500;
            const storedPreference = normalizePreference(
                localStorage.getItem(appThemeStorageKey) ?? preference
            );

            lastNativeThemeSwitcherClickAt = 0;

            if (!isManualNativeThemeClick) {
                if (storedPreference === 'true-black' && selectedTheme === 'dark') {
                    applyThemePreference('true-black');

                    return;
                }

                applyThemePreference(storedPreference);

                return;
            }

            await applyAndPersistThemePreference(selectedTheme);
        });

        const startThemeSwitcherObserver = () => {
            mountTrueBlackButtons();

            if (document.body) {
                new MutationObserver(() => mountTrueBlackButtons()).observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startThemeSwitcherObserver);
        } else {
            startThemeSwitcherObserver();
        }

        if (prefersDarkQuery.addEventListener) {
            prefersDarkQuery.addEventListener('change', () => {
                writeEffectiveThemeCookie();

                if (normalizePreference(localStorage.getItem(appThemeStorageKey) ?? preference) === 'system') {
                    applyThemePreference('system');
                }
            });
        }
    })();
</script>
