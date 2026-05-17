@php
    $authMethod = session('auth.method', 'local');
    $mfaRequired = $user->requiresTwoFactorAuthentication($authMethod);
    $hasTwoFactorSecret = filled($user->two_factor_secret);
    $hasConfirmedTwoFactor = $user->hasConfirmedTwoFactorAuthentication();
    $canEnableOptionalMfa = config('security.mfa.optional_for_users') || $mfaRequired;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Profil') }}</p>
                <h1 class="mt-2 text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Sicherheit') }}</h1>
            </div>

            <a href="{{ route('profile.edit') }}" class="btn btn-outline rounded-2xl">
                {{ __('Zurueck zum Profil') }}
            </a>
        </div>
    </x-slot>

    <div class="grid gap-5">
        @if (session('warning'))
            <div class="alert alert-warning rounded-3xl">
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success rounded-3xl">
                <span>
                    @switch(session('status'))
                        @case('two-factor-authentication-enabled')
                            {{ __('MFA wurde vorbereitet. Bitte bestaetige den Code aus deiner Authenticator-App.') }}
                            @break
                        @case('two-factor-authentication-confirmed')
                            {{ __('MFA wurde aktiviert.') }}
                            @break
                        @case('two-factor-authentication-disabled')
                            {{ __('MFA wurde deaktiviert.') }}
                            @break
                        @case('recovery-codes-generated')
                            {{ __('Neue Recovery Codes wurden erstellt.') }}
                            @break
                        @default
                            {{ __(session('status')) }}
                    @endswitch
                </span>
            </div>
        @endif

        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <h2 class="text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Zwei-Faktor-Authentifizierung') }}
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-base-content/70">
                        {{ __('MFA schuetzt dein Konto mit einem zusaetzlichen Einmalcode aus einer Authenticator-App.') }}
                    </p>
                </div>

                <div class="badge {{ $hasConfirmedTwoFactor ? 'badge-success' : ($hasTwoFactorSecret ? 'badge-warning' : 'badge-neutral') }} gap-2 px-4 py-3">
                    @if ($hasConfirmedTwoFactor)
                        {{ __('Aktiv') }}
                    @elseif ($hasTwoFactorSecret)
                        {{ __('Bestaetigung offen') }}
                    @else
                        {{ __('Nicht eingerichtet') }}
                    @endif
                </div>
            </div>

            @if ($mfaRequired)
                <div class="mt-5 rounded-2xl border border-warning/30 bg-warning/10 p-4 text-sm leading-7 text-base-content/80">
                    {{ __('Deine Rolle erfordert bestaetigte MFA. Bitte richte MFA ein, um fortzufahren.') }}
                </div>
            @endif

            @if (! $hasTwoFactorSecret && $canEnableOptionalMfa)
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
                    @csrf
                    <x-primary-button>
                        {{ __('MFA aktivieren') }}
                    </x-primary-button>
                </form>
            @elseif (! $hasTwoFactorSecret)
                <div class="mt-5 rounded-2xl border border-base-300 bg-base-200/70 p-4 text-sm leading-7 text-base-content/70">
                    {{ __('MFA kann fuer dein Konto aktuell nicht selbst aktiviert werden.') }}
                </div>
            @endif
        </section>

        @if ($hasTwoFactorSecret && ! $hasConfirmedTwoFactor)
            <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('MFA-Bestaetigung offen') }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                            {{ __('Der QR-Code und das MFA-Secret werden nur nach erneuter Passwortbestaetigung angezeigt. Oeffne die Einrichtung, um den QR-Code zu scannen und den sechsstelligen Code zu bestaetigen.') }}
                        </p>
                    </div>

                    <a href="{{ route('profile.security.two-factor-setup') }}" class="btn btn-primary rounded-2xl">
                        {{ __('MFA-Einrichtung fortsetzen') }}
                    </a>
                </div>
            </section>
        @endif

        @if ($hasConfirmedTwoFactor)
            <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Recovery Codes') }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                            {{ __('Bewahre diese Codes sicher auf. Jeder Code kann nur einmal verwendet werden, falls du keinen Zugriff auf deine Authenticator-App hast.') }}
                        </p>
                    </div>

                    <a href="{{ route('profile.security.recovery-codes') }}" class="btn btn-outline rounded-2xl">
                        {{ __('Recovery Codes anzeigen') }}
                    </a>
                </div>
            </section>

            <section class="rounded-3xl border border-error/30 bg-error/5 p-5 shadow-sm sm:p-6">
                <h2 class="text-xl font-semibold tracking-tight text-base-content">
                    {{ __('MFA deaktivieren') }}
                </h2>

                @if ($mfaRequired)
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('MFA ist fuer deine Rolle verpflichtend und kann hier nicht deaktiviert werden. Wende dich fuer einen Reset an einen Superadmin oder Betreiber mit Shell-Zugriff.') }}
                    </p>
                @else
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('Wenn du MFA deaktivierst, ist dein Konto nur noch durch dein Passwort geschuetzt.') }}
                    </p>

                    <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-5">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error rounded-2xl">
                            {{ __('MFA deaktivieren') }}
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
