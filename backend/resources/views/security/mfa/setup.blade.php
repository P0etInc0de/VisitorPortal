<x-guest-layout>
    <div class="mx-auto max-w-[34rem] overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="border-b border-base-300/70 px-4 pb-6 pt-6 text-center sm:px-7">
            <h1 class="mt-2 text-[clamp(1.05rem,4.8vw,1.75rem)] font-semibold leading-tight tracking-tight text-base-content" aria-label="{{ __('Zwei-Faktor-Authentifizierung einrichten') }}">
                <span class="block whitespace-nowrap">{{ __('Zwei-Faktor-Authentifizierung') }}</span>
                <span class="block">{{ __('einrichten') }}</span>
            </h1>
        </div>

        <div class="grid justify-items-center gap-5 p-6 sm:p-7">
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

            <section class="grid w-full justify-items-center gap-6">
                <div class="max-w-full justify-self-center overflow-hidden rounded-3xl border border-base-300 bg-white p-4 text-gray-900 [&_svg]:h-auto [&_svg]:max-w-full">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>

                <div class="w-full max-w-sm">
                    <form method="POST" action="{{ route('security.mfa.confirm') }}" class="grid gap-4">
                        @csrf

                        <p class="text-sm leading-7 text-base-content/70">
                            {{ __('Bitte Scanne den QR-Code mit deiner Authenticator-App, gebe den sechsstelligen Code ein und bestätige.') }}
                        </p>

                        <div>
                            <x-input-label for="code" :value="__('Sechsstelliger Code')" />
                            <x-totp-code-input id="code" class="mt-1 w-full" type="text" name="code" required autofocus />
                            <x-input-error :messages="$errors->confirmTwoFactorAuthentication->get('code')" class="mt-2" />
                        </div>

                        <x-primary-button>
                            {{ __('Bestätigen') }}
                        </x-primary-button>
                    </form>
                </div>
            </section>

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
