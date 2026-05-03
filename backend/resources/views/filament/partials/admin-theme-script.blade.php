<script>
    (function () {
        const preference = @js($themePreference);
        const syncUrl = @js($themeSyncUrl);
        const trueBlackLabel = @js($trueBlackLabel);
        const root = document.documentElement;
        const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;
        const activeButtonIndex = { light: 0, dark: 1, system: 2 };
        let manualThemeSwitcherClick = false;

        const syncThemeSwitcherUi = (themePreference) => {
            document.querySelectorAll('.fi-theme-switcher').forEach((switcher) => {
                const buttons = switcher.querySelectorAll('.fi-theme-switcher-btn');

                buttons.forEach((button) => button.classList.remove('fi-active'));

                if (themePreference === 'true-black') {
                    switcher.querySelector('[data-app-theme="true-black"]')?.classList.add('fi-active');
                    return;
                }

                const index = activeButtonIndex[themePreference];

                if (index !== undefined) {
                    buttons[index]?.classList.add('fi-active');
                }
            });
        };

        const applyThemePreference = (themePreference) => {
            localStorage.setItem('app-theme-preference', themePreference);

            if (themePreference === 'true-black') {
                root.setAttribute('data-theme', 'true-black');
                root.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                syncThemeSwitcherUi('true-black');
                return;
            }

            if (themePreference === 'dark') {
                root.setAttribute('data-theme', 'dark');
                root.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                syncThemeSwitcherUi('dark');
                return;
            }

            if (themePreference === 'light') {
                root.setAttribute('data-theme', 'light');
                root.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                syncThemeSwitcherUi('light');
                return;
            }

            root.setAttribute('data-theme', prefersDark() ? 'dark' : 'light');
            root.classList.toggle('dark', prefersDark());
            localStorage.setItem('theme', 'system');
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
                    body: JSON.stringify({ theme_preference: themePreference }),
                });
            } catch (error) {
                console.error('Unable to persist theme preference.', error);
            }
        };

        const closeThemeSwitcherDropdown = (button) => {
            button.closest('[x-data]')?.__x?.$data?.close?.();
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
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="7" /></svg>';

                button.addEventListener('click', async () => {
                    applyThemePreference('true-black');
                    await persistThemePreference('true-black');
                    closeThemeSwitcherDropdown(button);
                });

                switcher.appendChild(button);
            });

            syncThemeSwitcherUi(localStorage.getItem('app-theme-preference') ?? preference);
        };

        applyThemePreference(preference);

        document.addEventListener('click', (event) => {
            const button = event.target.closest?.('.fi-theme-switcher-btn');

            if (button && !button.dataset.appTheme) {
                manualThemeSwitcherClick = true;
            }
        }, true);

        document.addEventListener('theme-changed', async (event) => {
            const selectedTheme = typeof event.detail === 'string'
                ? event.detail
                : 'system';

            const storedPreference = localStorage.getItem('app-theme-preference') ?? preference;
            const wasManualThemeSwitcherClick = manualThemeSwitcherClick;

            manualThemeSwitcherClick = false;

            if (
                storedPreference === 'true-black'
                && selectedTheme === 'dark'
                && !wasManualThemeSwitcherClick
            ) {
                applyThemePreference('true-black');
                return;
            }

            if (!wasManualThemeSwitcherClick) {
                applyThemePreference(storedPreference);
                return;
            }

            if (selectedTheme === storedPreference) {
                applyThemePreference(selectedTheme);
                return;
            }

            applyThemePreference(selectedTheme);
            await persistThemePreference(selectedTheme);
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
    })();
</script>
