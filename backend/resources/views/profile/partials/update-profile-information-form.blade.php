<section>
    <header>
        <h2 class="text-xl font-semibold tracking-tight text-base-content">
            {{ __('Profileinstellungen') }}
        </h2>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-5 grid gap-5">
        @csrf
        @method('patch')

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="locale" :value="__('Sprache')" />
                <select id="locale" name="locale" class="select select-bordered h-12 min-h-12 w-full rounded-xl leading-normal">
                    <option value="de" @selected(old('locale', $user->locale ?? app()->getLocale()) === 'de')>🇩🇪 Deutsch</option>
                    <option value="en" @selected(old('locale', $user->locale ?? app()->getLocale()) === 'en')>🇬🇧 English</option>
                    <option value="fr" @selected(old('locale', $user->locale ?? app()->getLocale()) === 'fr')>🇫🇷 Français</option>
                    <option value="cs" @selected(old('locale', $user->locale ?? app()->getLocale()) === 'cs')>🇨🇿 Čeština</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('locale')" />
            </div>

            <div>
                <x-input-label for="theme_preference" :value="__('Theme')" />
                <select id="theme_preference" name="theme_preference" class="select select-bordered h-12 min-h-12 w-full rounded-xl leading-normal">
                    <option value="light" @selected(old('theme_preference', $user->theme_preference ?? ($themePreference ?? 'system')) === 'light')>{{ __('Hell') }}</option>
                    <option value="dark" @selected(old('theme_preference', $user->theme_preference ?? ($themePreference ?? 'system')) === 'dark')>{{ __('Dunkel') }}</option>
                    <option value="true-black" @selected(old('theme_preference', $user->theme_preference ?? ($themePreference ?? 'system')) === 'true-black')>{{ __('True Black (OLED)') }}</option>
                    <option value="system" @selected(old('theme_preference', $user->theme_preference ?? ($themePreference ?? 'system')) === 'system')>{{ __('System / Auto') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('theme_preference')" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
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
