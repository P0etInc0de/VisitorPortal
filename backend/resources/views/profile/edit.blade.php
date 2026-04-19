<x-app-layout>
    <x-slot name="header">
        <div class="bp-page-header">
            <div>
                <h1 class="bp-page-title">{{ __('Profil') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-5">
        <section class="bp-panel p-5 sm:p-6">
            <div class="max-w-3xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="bp-panel p-5 sm:p-6">
            <div class="max-w-3xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section class="bp-panel p-5 sm:p-6">
            <div class="max-w-3xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>
