@php
    $brandName = config('branding.name', 'VisitorPortal');
    $logoLightPath = config('branding.logo_light');
    $logoDarkPath = config('branding.logo_dark');
    $hasLogoLight = is_string($logoLightPath) && $logoLightPath !== '' && file_exists(public_path($logoLightPath));
    $hasLogoDark = is_string($logoDarkPath) && $logoDarkPath !== '' && file_exists(public_path($logoDarkPath));
    $ssoAvailable = config('sso.enabled') && in_array(config('sso.auth_mode'), ['local_and_sso', 'sso_only'], true);
    $ssoOnly = config('sso.auth_mode') === 'sso_only';
@endphp

<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="grid lg:grid-cols-[1fr_0.88fr]">
            <div class="border-b border-base-300/70 bg-base-100 px-7 pb-7 pt-5 sm:px-9 sm:pb-9 sm:pt-6 lg:border-b-0 lg:border-r">
                @if ($hasLogoLight || $hasLogoDark)
                    <div class="mb-2 flex justify-center">
                        @if ($hasLogoLight)
                            <img src="{{ asset($logoLightPath) }}" alt="{{ $brandName }}" class="logo-light max-h-28 w-auto object-contain sm:max-h-32 lg:max-h-36">
                        @endif
                        @if ($hasLogoDark)
                            <img src="{{ asset($logoDarkPath) }}" alt="{{ $brandName }}" class="logo-dark max-h-28 w-auto object-contain sm:max-h-32 lg:max-h-36">
                        @endif
                    </div>
                @endif

                <h1 class="mt-0 text-3xl font-semibold tracking-tight text-base-content sm:text-4xl">
                    {{ __('Visitor portal') }}
                </h1>
                <p class="mt-3 max-w-md text-sm leading-7 text-base-content/70 sm:text-base">
                    {{ __('Login_Textbeschreibung') }}
                </p>


            </div>

            <div class="bg-base-100/90 p-7 sm:p-9">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold tracking-tight">{{ __('Anmelden') }}</h2>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                @if ($ssoAvailable)
                    <a href="{{ route('auth.oidc.redirect') }}" class="btn btn-primary h-12 w-full rounded-xl">
                        {{ __('Sign in with :name', ['name' => config('sso.oidc.display_name')]) }}
                    </a>
                @endif

                @if ($ssoAvailable && ! $ssoOnly)
                    <div class="divider my-6">{{ __('oder') }}</div>
                @endif

                @if ($ssoOnly)
                    <details class="mt-5 rounded-2xl border border-base-300 bg-base-200/40 p-4">
                        <summary class="cursor-pointer text-sm font-medium text-base-content/70">
                            {{ __('Emergency local admin login') }}
                        </summary>
                        <div class="mt-5">
                @endif

                <form method="POST" action="{{ route('login') }}" class="{{ $ssoAvailable ? 'space-y-5' : 'space-y-5' }}">
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

                @if ($ssoOnly)
                        </div>
                    </details>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
