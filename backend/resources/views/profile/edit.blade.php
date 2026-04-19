<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">{{ __('Profil') }}</h1>
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
