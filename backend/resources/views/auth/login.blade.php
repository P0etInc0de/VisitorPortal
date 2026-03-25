@php
    // Optional logo from public/images/logo.png
    $hasLogo = file_exists(public_path('images/logo.png'));
@endphp

<x-guest-layout>
    <div class="rounded-[2rem] border border-base-300 bg-base-100 px-6 py-8 shadow-sm sm:px-10 sm:py-10">
        @if ($hasLogo)
            <div class="mb-5 flex justify-center">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Besucherportal Logo') }}"
                    class="max-h-20 w-auto object-contain"
                >
            </div>
        @endif

        <div class="mb-8 text-center">
            <h1 class="text-4xl font-semibold tracking-tight">
                {{ __('Besucherportal') }}
            </h1>
            <p class="mt-3 text-base text-base-content/70">
                {{ __('Anmeldung für Mitarbeitende') }}
            </p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-medium">
                    {{ __('E-Mail') }}
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="{{ __('max.mustermann@example.com') }}"
                    class="input input-bordered input-lg w-full rounded-2xl"
                >

                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium">
                    {{ __('Passwort') }}
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••••••"
                    class="input input-bordered input-lg w-full rounded-2xl"
                >

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <label for="remember_me" class="flex cursor-pointer items-center gap-3 text-sm">
                    <input
                        id="remember_me"
                        name="remember"
                        type="checkbox"
                        class="checkbox checkbox-sm"
                    >
                    <span>{{ __('Angemeldet bleiben') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-medium underline underline-offset-4 hover:opacity-80"
                    >
                        {{ __('Passwort vergessen?') }}
                    </a>
                @endif
            </div>

            <div class="pt-1">
                <button
                    type="submit"
                    class="btn btn-primary btn-lg min-w-48 rounded-2xl"
                >
                    {{ __('Anmelden') }}
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
