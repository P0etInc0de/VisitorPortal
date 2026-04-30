<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">{{ __('Profil') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-5">
        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <x-lang.language_switcher/>
        </section>

        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="max-w-3xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>
    </div>
</x-app-layout>
