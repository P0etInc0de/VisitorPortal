<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Dashboard') }}</h1>
            </div>
        </div>
    </x-slot>

    <livewire:reception.dashboard-page />
</x-app-layout>
