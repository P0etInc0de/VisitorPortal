@php
    $hasLogo = file_exists(public_path('images/logo.png'));
@endphp

<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="grid lg:grid-cols-[1fr_0.88fr]">
            <div class="border-b border-base-300/70 bg-base-100 p-7 sm:p-9 lg:border-b-0 lg:border-r">
                @if ($hasLogo)
                    <div class="mb-6 flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ __('Besucherportal Logo') }}" class="max-h-12 w-auto object-contain">
                    </div>
                @endif

                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-base-content sm:text-4xl">
                    {{ __('Besucherportal') }}
                </h1>
                <p class="mt-4 max-w-md text-sm leading-7 text-base-content/70 sm:text-base">
                    {{ __('Ein gemeinsamer Login für Mitarbeitende, Empfang und weitere berechtigte Rollen. Die Oberfläche passt sich anschließend an die vorhandenen Bereiche und Rechte an.') }}
                </p>


            </div>

            <div class="bg-base-100/90 p-7 sm:p-9">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold tracking-tight">{{ __('Anmelden') }}</h2>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium">{{ __('E-Mail') }}</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="max.mustermann@example.com"
                            class="input input-bordered h-12 w-full rounded-xl"
                        >
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium">{{ __('Passwort') }}</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••••••"
                            class="input input-bordered h-12 w-full rounded-xl"
                        >
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-sm">
                            <input id="remember_me" name="remember" type="checkbox" class="checkbox checkbox-sm">
                            <span>{{ __('Angemeldet bleiben') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:underline">
                                {{ __('Passwort vergessen?') }}
                            </a>
                        @endif
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary h-12 w-full rounded-xl">
                            {{ __('Anmelden') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
