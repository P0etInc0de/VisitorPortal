<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-bold leading-tight tracking-tight text-base-content">
            {{ __('Kein Monitor konfiguriert') }}
        </h1>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-base-content">{{ __('Für Ihren Standort ist kein Willkommensmonitor eingerichtet.') }}</h2>
        <p class="mt-3 text-sm leading-6 text-base-content/70">
            {{ __('Bitte wenden Sie sich an eine Administratorin oder einen Administrator.') }}
        </p>
    </div>
</x-app-layout>
