<x-app-layout pageClass="bp-av-page-shell">
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4 bp-av-screen-head">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight lg:text-5xl">{{ __('Alle Besuche') }}</h1>
            </div>
        </div>
    </x-slot>

    @livewire('reception.all-visits-page')
</x-app-layout>
