<x-app-layout pageClass="pt-[0.22rem] pb-5">
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Meine Besuche') }}</h1>
            </div>
        </div>
    </x-slot>

    @livewire('portal.my-visits-page')
</x-app-layout>
