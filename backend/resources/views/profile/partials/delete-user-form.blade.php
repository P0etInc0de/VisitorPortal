<section class="grid gap-4">
    <header>
        <h2 class="text-xl font-semibold tracking-tight text-base-content">
            {{ __('Konto löschen') }}
        </h2>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Konto löschen') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')

            <h2 class="text-xl font-semibold tracking-tight text-base-content">
                {{ __('Konto wirklich löschen?') }}
            </h2>

            <p class="mt-2 text-sm leading-7 text-base-content/70">
                {{ __('Bitte zur Bestätigung das aktuelle Passwort eingeben.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Passwort') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Passwort') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Abbrechen') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Konto löschen') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
