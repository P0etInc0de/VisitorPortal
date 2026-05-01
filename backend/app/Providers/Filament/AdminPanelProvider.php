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
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
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
            ->brandLogo(asset('images/logo.png'))
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
                AccountWidget::class,
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
                    @endphp

                    <script>
                        (function () {
                            const preference = @js($themePreference)
                            const root = document.documentElement

                            if (preference === 'true-black') {
                                root.setAttribute('data-theme', 'true-black')
                                root.classList.add('dark')
                                localStorage.setItem('theme', 'dark')

                                return
                            }

                            if (preference === 'dark') {
                                root.setAttribute('data-theme', 'dark')
                                localStorage.setItem('theme', 'dark')

                                return
                            }

                            if (preference === 'light') {
                                root.setAttribute('data-theme', 'light')
                                root.classList.remove('dark')
                                localStorage.setItem('theme', 'light')

                                return
                            }

                            root.removeAttribute('data-theme')
                            localStorage.setItem('theme', 'system')
                        })()
                    </script>

                    <style>
                        html[data-theme="true-black"] {
                            color-scheme: dark;
                        }

                        html[data-theme="true-black"] body,
                        html[data-theme="true-black"] .fi-layout,
                        html[data-theme="true-black"] .fi-main,
                        html[data-theme="true-black"] .fi-sidebar,
                        html[data-theme="true-black"] .fi-topbar,
                        html[data-theme="true-black"] .fi-header,
                        html[data-theme="true-black"] .fi-section,
                        html[data-theme="true-black"] .fi-dropdown-panel,
                        html[data-theme="true-black"] .fi-modal-window {
                            background-color: #000 !important;
                        }

                        html[data-theme="true-black"] .fi-section,
                        html[data-theme="true-black"] .fi-sidebar,
                        html[data-theme="true-black"] .fi-topbar,
                        html[data-theme="true-black"] .fi-dropdown-panel,
                        html[data-theme="true-black"] .fi-modal-window,
                        html[data-theme="true-black"] .fi-ta-record,
                        html[data-theme="true-black"] .fi-input-wrp,
                        html[data-theme="true-black"] .fi-input,
                        html[data-theme="true-black"] .fi-select-input,
                        html[data-theme="true-black"] .fi-textarea {
                            border-color: rgb(38 38 38 / 1) !important;
                        }

                        html[data-theme="true-black"] .fi-section,
                        html[data-theme="true-black"] .fi-dropdown-panel,
                        html[data-theme="true-black"] .fi-modal-window,
                        html[data-theme="true-black"] .fi-ta-record,
                        html[data-theme="true-black"] .fi-input-wrp,
                        html[data-theme="true-black"] .fi-input,
                        html[data-theme="true-black"] .fi-select-input,
                        html[data-theme="true-black"] .fi-textarea {
                            background-color: #050505 !important;
                        }
                    </style>
                    BLADE
                )
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('
                    <x-filament::button tag="a" href="/overview" size="sm">
                        {{__("User-Bereich")}}
                    </x-filament::button>
                ')
            );
    }
}
