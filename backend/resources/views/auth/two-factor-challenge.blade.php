<x-guest-layout>
    <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('Zwei-Faktor-Code eingeben') }}</h1>
            <p class="mt-3 text-sm leading-7 text-base-content/70">
                {{ __('Bitte gib den sechsstelligen Code aus deiner Authenticator-App ein.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Authenticator-Code')" />
                <x-totp-code-input id="code" class="mt-1 w-full" type="text" name="code" autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <details class="group pt-1" @if ($errors->has('recovery_code')) open @endif>
                <summary
                    data-testid="mfa-recovery-toggle"
                    class="inline-flex cursor-pointer list-none items-center gap-2 rounded-xl px-1 py-1 text-sm font-medium text-base-content/70 transition hover:text-base-content focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 [&::-webkit-details-marker]:hidden"
                >
                    <span>{{ __('Recovery') }}</span>
                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                    </svg>
                </summary>

                <div class="mt-3">
                    <x-input-label for="recovery_code" :value="__('Recovery Code')" />
                    <input
                        id="recovery_code"
                        class="mt-1 block h-12 w-full rounded-xl border border-base-300 bg-base-100 px-4 text-base-content shadow-sm placeholder:text-base-content/40 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        type="text"
                        name="recovery_code"
                        autocomplete="one-time-code"
                    >
                    <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
                </div>
            </details>

            <div class="flex items-center justify-end pt-2">
                <x-primary-button>
                    {{ __('Anmelden') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
