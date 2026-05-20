@php
    $brandName = config('branding.name', 'VisitorPortal');
    $logoLightPath = config('branding.logo_light');
    $logoDarkPath = config('branding.logo_dark');
    $hasLogoLight = is_string($logoLightPath) && $logoLightPath !== '' && file_exists(public_path($logoLightPath));
    $hasLogoDark = is_string($logoDarkPath) && $logoDarkPath !== '' && file_exists(public_path($logoDarkPath));
@endphp

<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="border-b border-base-300/70 px-7 pb-6 pt-6 text-center sm:px-9">
            @if ($hasLogoLight || $hasLogoDark)
                <div class="mb-3 flex justify-center">
                    @if ($hasLogoLight)
                        <img src="{{ asset($logoLightPath) }}" alt="{{ $brandName }}" class="logo-light max-h-24 w-auto object-contain">
                    @endif
                    @if ($hasLogoDark)
                        <img src="{{ asset($logoDarkPath) }}" alt="{{ $brandName }}" class="logo-dark max-h-24 w-auto object-contain">
                    @endif
                </div>
            @endif

            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-base-content">{{ __('Zwei-Faktor-Authentifizierung erforderlich') }}</h1>
        </div>

        <div class="grid gap-5 p-7 sm:p-9">
            @if (session('status'))
                <div class="alert alert-success rounded-2xl">
                    <span>{{ __(session('status')) }}</span>
                </div>
            @endif

            <div class="rounded-2xl border border-base-300 bg-base-200/60 p-4 text-sm leading-7 text-base-content/75">
                {{ __('Bitte richte die Zwei-Faktor-Authentifizierung mit einer Authenticator-App ein. Danach wirst du weitergeleitet.') }}
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('security.mfa.setup') }}" class="btn btn-primary rounded-2xl">
                    {{ __('Einrichten starten') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline rounded-2xl">
                        {{ __('Abmelden') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
