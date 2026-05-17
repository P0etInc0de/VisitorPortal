<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Sicherheit') }}</p>
                <h1 class="mt-2 text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('MFA einrichten') }}</h1>
            </div>

            <a href="{{ route('profile.security') }}" class="btn btn-outline rounded-2xl">
                {{ __('Zurueck zur Sicherheit') }}
            </a>
        </div>
    </x-slot>

    <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
        <div class="grid gap-6 lg:grid-cols-[16rem_1fr] lg:items-start">
            <div class="rounded-3xl border border-base-300 bg-white p-4 text-gray-900">
                {!! $user->twoFactorQrCodeSvg() !!}
            </div>

            <div>
                <h2 class="text-xl font-semibold tracking-tight text-base-content">
                    {{ __('Authenticator-App verbinden') }}
                </h2>
                <p class="mt-3 text-sm leading-7 text-base-content/70">
                    {{ __('Bitte scanne den QR-Code mit deiner Authenticator-App und bestaetige anschliessend den sechsstelligen Code. MFA ist erst nach dieser Bestaetigung aktiv.') }}
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="mt-5 grid gap-4 sm:max-w-sm">
                    @csrf

                    <div>
                        <x-input-label for="code" :value="__('Sechsstelliger Code')" />
                        <x-text-input id="code" class="mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus />
                        <x-input-error :messages="$errors->confirmTwoFactorAuthentication->get('code')" class="mt-2" />
                    </div>

                    <div>
                        <x-primary-button>
                            {{ __('MFA bestaetigen') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>
