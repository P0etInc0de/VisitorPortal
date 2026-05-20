@php
    $brandName = config('branding.name', 'VisitorPortal');
    $hasTwoFactorSecret = filled($user->two_factor_secret);
@endphp

<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="border-b border-base-300/70 px-7 pb-6 pt-6 text-center sm:px-9">
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-base-content">{{ __('Zwei-Faktor-Authentifizierung einrichten') }}</h1>
        </div>

        <div class="grid gap-5 p-7 sm:p-9">
            @if (session('status'))
                <div class="alert alert-success rounded-2xl">
                    <span>
                        @if (session('status') === 'two-factor-authentication-enabled')
                            {{ __('Zwei-Faktor-Authentifizierung wurde vorbereitet. Bitte bestätige den Code aus deiner Authenticator-App.') }}
                        @else
                            {{ __(session('status')) }}
                        @endif
                    </span>
                </div>
            @endif

            @if (! $hasTwoFactorSecret)
                <section class="rounded-2xl border border-base-300 bg-base-200/60 p-4">
                    <h2 class="text-lg font-semibold tracking-tight text-base-content">{{ __('Authenticator-App vorbereiten') }}</h2>
                    <p class="mt-3 text-sm leading-7 text-base-content/70">
                        {{ __('Starte die Einrichtung. Danach wird dir ein QR-Code angezeigt, den du mit deiner Authenticator-App scannst.') }}
                    </p>

                    <form method="POST" action="{{ route('security.mfa.enable') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="btn btn-primary rounded-2xl">
                            {{ __('Einrichtung starten') }}
                        </button>
                    </form>
                </section>
            @else
                <section class="grid gap-6 lg:grid-cols-[14rem_1fr] lg:items-start">
                    <div class="rounded-3xl border border-base-300 bg-white p-4 text-gray-900">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>

                    <div>
                        <form method="POST" action="{{ route('security.mfa.confirm') }}" class="grid gap-4 sm:max-w-sm">
                            @csrf

                            <div>
                                <x-input-label for="code" :value="__('Sechsstelliger Code')" />
                                <x-totp-code-input id="code" class="mt-1 h-12 w-full rounded-xl border border-base-300 bg-base-100 px-4 text-base-content shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" type="text" name="code" required autofocus />
                                <x-input-error :messages="$errors->confirmTwoFactorAuthentication->get('code')" class="mt-2" />
                            </div>

                            <x-primary-button>
                                {{ __('Bestätigen') }}
                            </x-primary-button>
                        </form>
                    </div>
                </section>
            @endif

            <div class="flex flex-col gap-3 border-t border-base-300 pt-5 sm:flex-row">
                <a href="{{ route('security.mfa.required') }}" class="btn btn-outline rounded-2xl">
                    {{ __('Zurück') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost rounded-2xl">
                        {{ __('Abmelden') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
