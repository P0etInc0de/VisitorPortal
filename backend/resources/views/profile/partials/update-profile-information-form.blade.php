<section>
    <header>
        <h2 class="text-xl font-semibold tracking-tight text-base-content">
            {{ __('Profilinformationen') }}
        </h2>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 bp-form-stack">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('E-Mail')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 bp-inline-note">
                    <p class="text-sm text-base-content/80">
                        {{ __('Ihre E-Mail-Adresse ist noch nicht verifiziert.') }}
                    </p>

                    <button form="send-verification" class="mt-3 text-sm font-medium text-primary hover:underline">
                        {{ __('Bestätigungs-E-Mail erneut senden') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-3 text-sm text-success">
                            {{ __('Ein neuer Bestätigungslink wurde versendet.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="bp-action-row">
            <x-primary-button>{{ __('Speichern') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-base-content/65"
                >{{ __('Gespeichert.') }}</p>
            @endif
        </div>
    </form>
</section>
