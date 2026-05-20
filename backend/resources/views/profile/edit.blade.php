<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:justify-between">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Profil') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-5">
        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="max-w-3xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        @if ($user->local_login_allowed === false)
            <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="max-w-3xl">
                    <h2 class="text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Passwort') }}
                    </h2>
                    <p class="mt-3 text-sm leading-7 text-base-content/70">
                        {{ __('Dieses Konto wird über SSO verwaltet. Das lokale Passwort kann hier nicht geändert werden.') }}
                    </p>
                </div>
            </section>
        @else
            <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="max-w-3xl">
                    @include('profile.partials.update-password-form')
                </div>
            </section>
        @endif

        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Sicherheit') }}
                    </h2>
                    <p class="mt-2 text-sm leading-7 text-base-content/70">
                        {{ __('Zwei-Faktor-Authentifizierung einrichten, Recovery Codes verwalten und Kontosicherheit prüfen.') }}
                    </p>
                </div>

                <a href="{{ route('profile.security') }}" class="btn btn-outline rounded-2xl">
                    {{ __('Sicherheit öffnen') }}
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
