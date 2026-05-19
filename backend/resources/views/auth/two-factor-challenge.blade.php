<x-guest-layout>
    <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('Zwei-Faktor-Code eingeben') }}</h1>
            <p class="mt-3 text-sm leading-7 text-base-content/70">
                {{ __('Bitte gib den sechsstelligen Code aus deiner Authenticator-App ein. Alternativ kannst du einen Recovery Code verwenden.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Authenticator-Code')" />
                <x-text-input id="code" class="mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div class="divider text-xs uppercase tracking-[0.18em] text-base-content/45">{{ __('oder') }}</div>

            <div>
                <x-input-label for="recovery_code" :value="__('Recovery Code')" />
                <x-text-input id="recovery_code" class="mt-1 w-full" type="text" name="recovery_code" autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end pt-2">
                <x-primary-button>
                    {{ __('Anmelden') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
