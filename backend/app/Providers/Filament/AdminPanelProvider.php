<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\ApplyUserPreferences;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->brandLogo(new HtmlString('
                <img src="'.asset('images/logo-light.png').'" class="fi-logo logo-light h-10 w-auto" alt="Logo">
                <img src="'.asset('images/logo-dark.png').'" class="fi-logo logo-dark h-10 w-auto" alt="Logo">
            '))
            ->brandLogoHeight('4rem')
            ->colors([
                // 'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ApplyUserPreferences::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): string => Blade::render(
                    <<<'BLADE'
                    @php
                        $themePreference = \App\Support\UserPreferences::normalizeTheme(auth()->user()?->theme_preference)
                            ?? \App\Support\UserPreferences::THEME_SYSTEM;
                        $themeSyncUrl = route('profile.theme-preference.update');
                        $trueBlackLabel = __('True Black (OLED)');
                    @endphp

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

                    <style>
                        :root {
                            --bp-admin-border: rgb(229 231 235 / 1);
                            --bp-admin-border-strong: rgb(209 213 219 / 1);
                            --bp-admin-focus-border: rgb(245 158 11 / 1);
                            --bp-admin-panel-bg: rgb(255 255 255 / 1);
                            --bp-admin-page-bg: rgb(249 250 251 / 1);
                        }

                        html.dark,
                        html[data-theme="dark"] {
                            --bp-admin-border: rgb(63 63 70 / 1);
                            --bp-admin-border-strong: rgb(82 82 91 / 1);
                            --bp-admin-panel-bg: rgb(24 24 27 / 1);
                            --bp-admin-page-bg: rgb(9 9 11 / 1);
                        }

                        html[data-theme="true-black"] {
                            color-scheme: dark;
                            --bp-admin-border: rgb(38 38 38 / 1);
                            --bp-admin-border-strong: rgb(64 64 64 / 1);
                            --bp-admin-panel-bg: #050505;
                            --bp-admin-page-bg: #000;
                        }

                        /*
                           Stable admin borders:
                           Filament often renders subtle borders via rings and shadows.
                           During browser zoom these can become partially invisible due to
                           subpixel rendering. The pseudo-border below keeps rounded panel,
                           input and button borders stable without removing Filament's own
                           focus rings or shadows.
                        */
                        .fi-section,
                        .fi-dropdown-panel,
                        .fi-modal-window,
                        .fi-input-wrp,
                        .fi-btn,
                        .fi-icon-btn,
                        .fi-theme-switcher-btn {
                            position: relative;
                            background-clip: padding-box !important;
                        }

                        .fi-section::after,
                        .fi-dropdown-panel::after,
                        .fi-modal-window::after,
                        .fi-input-wrp::after,
                        .fi-btn::after,
                        .fi-icon-btn::after,
                        .fi-theme-switcher-btn::after {
                            content: "";
                            position: absolute;
                            inset: 0;
                            z-index: 1;
                            pointer-events: none;
                            border: 1px solid var(--bp-admin-border);
                            border-radius: inherit;
                        }

                        .fi-input-wrp:focus-within::after,
                        .fi-btn:focus-visible::after,
                        .fi-icon-btn:focus-visible::after,
                        .fi-theme-switcher-btn:focus-visible::after {
                            border-color: var(--bp-admin-focus-border);
                        }

                        /*
                           Clip inner fill colors to rounded wrapper corners.
                           This fixes square fill artifacts on the right side of inputs,
                           search fields, selects and buttons while keeping visible borders.
                        */
                        .fi-input-wrp,
                        .fi-btn,
                        .fi-icon-btn,
                        .fi-theme-switcher-btn {
                            overflow: hidden !important;
                        }

                        .fi-input-wrp .fi-input,
                        .fi-input-wrp .fi-select-input,
                        .fi-input-wrp .fi-textarea {
                            background-color: transparent !important;
                            background-clip: padding-box !important;
                        }

                        /*
                           True Black theme surfaces.
                           These override only colors, not Filament's border/focus mechanics.
                        */
                        html[data-theme="true-black"] body,
                        html[data-theme="true-black"] .fi-layout,
                        html[data-theme="true-black"] .fi-main,
                        html[data-theme="true-black"] .fi-sidebar,
                        html[data-theme="true-black"] .fi-topbar,
                        html[data-theme="true-black"] .fi-header {
                            background-color: var(--bp-admin-page-bg) !important;
                        }

                        html[data-theme="true-black"] .fi-section,
                        html[data-theme="true-black"] .fi-dropdown-panel,
                        html[data-theme="true-black"] .fi-modal-window,
                        html[data-theme="true-black"] .fi-input-wrp {
                            background-color: var(--bp-admin-panel-bg) !important;
                            border-color: var(--bp-admin-border) !important;
                        }

                        html[data-theme="true-black"] .fi-ta-record {
                            background-color: var(--bp-admin-panel-bg) !important;
                            border-color: var(--bp-admin-border) !important;
                        }

                        html[data-theme="true-black"] .fi-input,
                        html[data-theme="true-black"] .fi-select-input,
                        html[data-theme="true-black"] .fi-textarea {
                            border-color: var(--bp-admin-border) !important;
                        }

                        html[data-theme="true-black"] .fi-theme-switcher-btn.fi-active {
                            background-color: rgb(139 147 255 / 0.18) !important;
                            color: rgb(199 210 254 / 1) !important;
                        }

                        /* Logo switching for Filament Admin */
                        .fi-logo.logo-dark {
                            display: none;
                        }

                        .fi-logo.logo-light {
                            display: inline-block;
                        }

                        html[data-theme="dark"] .fi-logo.logo-light,
                        html[data-theme="true-black"] .fi-logo.logo-light {
                            display: none;
                        }

                        html[data-theme="dark"] .fi-logo.logo-dark,
                        html[data-theme="true-black"] .fi-logo.logo-dark {
                            display: inline-block;
                        }

                        @media (prefers-color-scheme: dark) {
                            html[data-theme="system"] .fi-logo.logo-light {
                                display: none;
                            }

                            html[data-theme="system"] .fi-logo.logo-dark {
                                display: inline-block;
                            }
                        }
                    </style>
                    BLADE
                )
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn (): string => Blade::render('filament.sidebar-footer')
            );
    }
}
