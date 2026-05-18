@php
    $brandName = config('branding.name', 'VisitorPortal');
    $logoLightPath = config('branding.logo_light');
    $logoDarkPath = config('branding.logo_dark');
    $hasLogoLight = is_string($logoLightPath) && $logoLightPath !== '' && file_exists(public_path($logoLightPath));
    $hasLogoDark = is_string($logoDarkPath) && $logoDarkPath !== '' && file_exists(public_path($logoDarkPath));
    $hasTwoFactorSecret = filled($user->two_factor_secret);
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

            <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Security onboarding') }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-base-content">{{ __('MFA erforderlich') }}</h1>
            <p class="mt-3 text-sm leading-7 text-base-content/70">
                {{ __('Dein Konto muss mit lokaler App-MFA abgesichert werden, bevor du VisitorPortal weiter nutzt.') }}
            </p>
        </div>

        <div class="grid gap-5 p-7 sm:p-9">
            @if (session('warning'))
                <div class="alert alert-warning rounded-2xl">
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success rounded-2xl">
                    <span>{{ __(session('status')) }}</span>
                </div>
            @endif

            <div class="rounded-2xl border border-base-300 bg-base-200/60 p-4 text-sm leading-7 text-base-content/75">
                {{ __('Richte MFA mit einer Authenticator-App ein und bestaetige anschliessend den sechsstelligen Code. Danach wirst du zu deinem urspruenglichen Ziel weitergeleitet.') }}
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('security.mfa.setup') }}" class="btn btn-primary rounded-2xl">
                    {{ $hasTwoFactorSecret ? __('MFA-Einrichtung fortsetzen') : __('MFA einrichten') }}
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
