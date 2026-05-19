@php
    $mfaRequiredForLogin = $mfaRequiredForLogin ?? false;
    $mfaRequiredForAdminPanel = $mfaRequiredForAdminPanel ?? false;
    $mfaRequiredForAnyContext = $mfaRequiredForAnyContext ?? false;
    $hasTwoFactorSecret = filled($user->two_factor_secret);
    $hasConfirmedTwoFactor = $user->hasConfirmedTwoFactorAuthentication();
    $localLoginDisabled = $user->local_login_allowed === false;
    $canEnableOptionalMfa = ! $localLoginDisabled && (config('security.mfa.optional_for_users') || $mfaRequiredForAnyContext);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Profil') }}</p>
                <h1 class="mt-2 text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Sicherheit') }}</h1>
            </div>

            <a href="{{ route('profile.edit') }}" class="btn btn-outline rounded-2xl">
                {{ __('Zurück zum Profil') }}
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
                            {{ __('Zwei-Faktor-Authentifizierung wurde vorbereitet. Bitte bestätige den Code aus deiner Authenticator-App.') }}
                            @break
                        @case('two-factor-authentication-confirmed')
                            {{ __('Zwei-Faktor-Authentifizierung wurde aktiviert.') }}
                            @break
                        @case('two-factor-authentication-disabled')
                            {{ __('Zwei-Faktor-Authentifizierung wurde deaktiviert.') }}
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
                        {{ __('Die Zwei-Faktor-Authentifizierung schützt dein Konto mit einem zusätzlichen Einmalcode aus einer Authenticator-App.') }}
                    </p>
                </div>

                <div class="badge {{ $hasConfirmedTwoFactor ? 'badge-success' : ($hasTwoFactorSecret ? 'badge-warning' : 'badge-neutral') }} gap-2 px-4 py-3">
                    @if ($hasConfirmedTwoFactor)
                        {{ __('Aktiv') }}
                    @elseif ($hasTwoFactorSecret)
                        {{ __('Bestätigung offen') }}
                    @else
                        {{ __('Nicht eingerichtet') }}
                    @endif
                </div>
            </div>

            <div class="mt-5 grid gap-3 text-sm leading-7 text-base-content/80">
                @if ($mfaRequiredForLogin)
                    <div class="rounded-2xl border border-warning/30 bg-warning/10 p-4">
                        {{ __('Zwei-Faktor-Authentifizierung ist für deinen Login verpflichtend.') }}
                    </div>
                @endif

                @if ($mfaRequiredForAdminPanel)
                    <div class="rounded-2xl border border-warning/30 bg-warning/10 p-4">
                        {{ $hasTwoFactorSecret
                            ? __('Zwei-Faktor-Authentifizierung ist für den Adminbereich verpflichtend.')
                            : __('Für den Adminbereich ist Zwei-Faktor-Authentifizierung erforderlich. Beim Zugriff auf den Adminbereich wirst du durch die Einrichtung geführt.') }}
                    </div>
                @endif

                @if ($localLoginDisabled)
                    <div class="rounded-2xl border border-info/30 bg-info/10 p-4">
                        {{ $mfaRequiredForAdminPanel || $mfaRequiredForAnyContext
                            ? __('Dieses Konto wird über SSO verwaltet. Für den Zugriff auf den Adminbereich kann zusätzlich lokale VisitorPortal-Zwei-Faktor-Authentifizierung erforderlich sein.')
                            : __('Dieses Konto wird über SSO verwaltet. Optionale Zwei-Faktor-Aktivierung über das Profil ist für dieses Konto nicht verfügbar.') }}
                    </div>
                @elseif (! $mfaRequiredForAnyContext)
                    <div class="rounded-2xl border border-base-300 bg-base-200/70 p-4 text-base-content/70">
                        {{ __('Zwei-Faktor-Authentifizierung ist für dieses Konto optional.') }}
                    </div>
                @endif
            </div>

            @if (! $hasTwoFactorSecret && $canEnableOptionalMfa)
                <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-6">
                    @csrf
                    <x-primary-button>
                        {{ __('Zwei-Faktor-Authentifizierung aktivieren') }}
                    </x-primary-button>
                </form>
            @elseif (! $localLoginDisabled && ! $hasTwoFactorSecret)
                <div class="mt-5 rounded-2xl border border-base-300 bg-base-200/70 p-4 text-sm leading-7 text-base-content/70">
                    {{ __('Zwei-Faktor-Authentifizierung kann für dein Konto aktuell nicht selbst aktiviert werden.') }}
                </div>
            @endif
        </section>

        @if ($hasTwoFactorSecret && ! $hasConfirmedTwoFactor)
            <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Zwei-Faktor-Bestätigung offen') }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                            {{ $localLoginDisabled
                                ? __('Dieses Konto wird über SSO verwaltet. Bitte nutze das verpflichtende Sicherheits-Onboarding, falls Zwei-Faktor-Authentifizierung für dein Konto erforderlich ist.')
                                : __('Der QR-Code und das Zwei-Faktor-Secret werden nur nach erneuter Passwortbestätigung angezeigt. Öffne die Einrichtung, um den QR-Code zu scannen und den sechsstelligen Code zu bestätigen.') }}
                        </p>
                    </div>

                    @unless ($localLoginDisabled)
                        <a href="{{ route('profile.security.two-factor-setup') }}" class="btn btn-primary rounded-2xl">
                            {{ __('Einrichtung fortsetzen') }}
                        </a>
                    @endunless
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
                    {{ __('Zwei-Faktor-Authentifizierung deaktivieren') }}
                </h2>

                @if ($mfaRequiredForAnyContext)
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('Zwei-Faktor-Authentifizierung kann für dieses Konto nicht deaktiviert werden, weil sie für mindestens einen Sicherheitskontext verpflichtend ist.') }}
                    </p>
                @elseif ($localLoginDisabled)
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('Dieses Konto wird über SSO verwaltet. Änderungen an der Zwei-Faktor-Konfiguration sind im Profil nicht verfügbar.') }}
                    </p>
                @else
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('Wenn du die Zwei-Faktor-Authentifizierung deaktivierst, ist dein Konto nur noch durch dein Passwort geschützt.') }}
                    </p>

                    <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-5">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error rounded-2xl">
                            {{ __('Zwei-Faktor-Authentifizierung deaktivieren') }}
                        </button>
                    </form>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
