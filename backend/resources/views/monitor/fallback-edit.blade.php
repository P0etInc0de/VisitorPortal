<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
            {{ __('Allgemeine Begrüßungsseite bearbeiten') }}
        </h1>
    </x-slot>

    <x-bp.card title="{{ __('Allgemeine Begrüßungsseite') }}" subtitle="{{ __('Diese Seite ist immer verfügbar und kann nicht gelöscht werden.') }}">
        <x-slot:actions>
            <div>
                <input form="editFallbackPage" type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{ __('Speichern') }}"/>
                <a type="button" class="btn-sm rounded-xl btn" href="{{ route('monitors.edit', $monitor) }}">{{ __('Abbrechen') }}</a>
            </div>
        </x-slot:actions>

        <form id="editFallbackPage" method="POST" action="{{ route('monitors.fallback.update', $monitor) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{ old('heading', $monitor->fallback_heading) }}"/>
                <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" value="{{ old('subheading', $monitor->fallback_subheading) }}"/>

                <x-bp.toggle label="{{ __('Logo Anzeigen') }}" name="show_logo" checked="{{ old('show_logo', $monitor->fallback_show_logo) }}"/>
                <x-bp.toggle label="{{ __('Datum Anzeigen') }}" name="show_date" checked="{{ old('show_date', $monitor->fallback_show_date) }}"/>

                @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null, 'isFallbackPage' => true])
            </div>
        </form>
    </x-bp.card>
</x-app-layout>
