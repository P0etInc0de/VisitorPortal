{{--
SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
SPDX-License-Identifier: GPL-3.0-or-later
--}}

@if (auth()->check())
    <x-app-layout>
        <section class="mx-auto max-w-xl rounded-3xl border border-base-300 bg-base-100 p-7 text-center shadow-sm sm:p-9">
            <p class="text-6xl font-black tracking-tight text-base-content/20 sm:text-7xl">403</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-base-content">
                {{ __('Zugriff verweigert') }}
            </h1>

            <div class="mx-auto mt-4 max-w-md space-y-3 text-base-content/70">
                <p>{{ __('Du hast keine Berechtigung für diese Seite oder Aktion.') }}</p>
                <p>{{ __('Falls du der Meinung bist, dass du Zugriff benötigst, kontaktiere bitte einen Administrator.') }}</p>
            </div>

            <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <button
                    type="button"
                    class="btn btn-outline rounded-xl"
                    onclick="window.history.length > 1 ? window.history.back() : window.location.assign(@js(route('home')))"
                >
                    {{ __('Zurück') }}
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-xl">
                        {{ __('Abmelden') }}
                    </button>
                </form>
            </div>
        </section>
    </x-app-layout>
@else
    <x-guest-layout title="{{ __('Zugriff verweigert') }}">
        <section class="mx-auto max-w-xl rounded-3xl border border-base-300 bg-base-100 p-7 text-center shadow-sm sm:p-9">
            <p class="text-6xl font-black tracking-tight text-base-content/20 sm:text-7xl">403</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-base-content">
                {{ __('Zugriff verweigert') }}
            </h1>
            <p class="mx-auto mt-4 max-w-md leading-7 text-base-content/70">
                {{ __('Du hast keine Berechtigung für diese Seite.') }}
            </p>

            <div class="mt-7 flex justify-center">
                <a href="{{ route('login') }}" class="btn btn-primary rounded-xl">
                    {{ __('Zur Anmeldung') }}
                </a>
            </div>
        </section>
    </x-guest-layout>
@endif
