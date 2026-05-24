{{--
SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
SPDX-License-Identifier: GPL-3.0-or-later
--}}

@if (auth()->check())
    <x-app-layout>
        <x-slot name="header">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-base-content/55">403</p>
                <h1 class="mt-2 text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
                    {{ __('Zugriff verweigert') }}
                </h1>
            </div>
        </x-slot>

        <section class="mx-auto max-w-2xl rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-8">
            <div class="grid gap-4">
                <p class="text-lg font-semibold text-base-content">
                    {{ __('Du hast keine Berechtigung für diese Seite oder Aktion.') }}
                </p>
                <p class="leading-7 text-base-content/70">
                    {{ __('Falls du der Meinung bist, dass du Zugriff benötigst, kontaktiere bitte einen Administrator.') }}
                </p>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
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
        <section class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-base-content/55">403</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-base-content">
                {{ __('Zugriff verweigert') }}
            </h1>
            <p class="mt-4 leading-7 text-base-content/70">
                {{ __('Du hast keine Berechtigung für diese Seite.') }}
            </p>

            <div class="mt-6">
                <a href="{{ route('login') }}" class="btn btn-primary rounded-xl">
                    {{ __('Zur Anmeldung') }}
                </a>
            </div>
        </section>
    </x-guest-layout>
@endif
