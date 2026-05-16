<x-app-layout>
    <x-slot name="header">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
            {{ __('Allgemeine Begrüßungsseite bearbeiten') }}
        </h1>
    </x-slot>

    <x-bp.card subtitle="{{ __('Die allgemeine Begrüßungsseite ist immer verfügbar und kann nicht gelöscht werden.') }}">
        <form id="editFallbackPage" method="POST" action="{{ route('monitors.fallback.update', $monitor) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid gap-5">
                <x-bp.input label="{{ __('Überschrift') }}" name="heading" :value="old('heading', $monitor->fallback_heading)"/>
                <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" :value="old('subheading', $monitor->fallback_subheading)"/>

                <div class="grid gap-3 sm:grid-cols-2 xl:max-w-[28rem]">
                    <div class="rounded-2xl border border-base-300 bg-base-200 p-3.5">
                        <label class="flex cursor-pointer flex-col items-start gap-3">
                            <span class="text-sm font-medium text-base-content">{{ __('Logo Anzeigen') }}</span>

                            <span class="shrink-0">
                                <input type="hidden" name="show_logo" value="0">
                                <input
                                    type="checkbox"
                                    name="show_logo"
                                    value="1"
                                    @checked(old('show_logo', $monitor->fallback_show_logo))
                                    class="toggle toggle-primary toggle-sm transition-all"
                                >
                            </span>
                        </label>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-200 p-3.5">
                        <label class="flex cursor-pointer flex-col items-start gap-3">
                            <span class="text-sm font-medium text-base-content">{{ __('Datum Anzeigen') }}</span>

                            <span class="shrink-0">
                                <input type="hidden" name="show_date" value="0">
                                <input
                                    type="checkbox"
                                    name="show_date"
                                    value="1"
                                    @checked(old('show_date', $monitor->fallback_show_date))
                                    class="toggle toggle-primary toggle-sm transition-all"
                                >
                            </span>
                        </label>
                    </div>
                </div>

                @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null, 'isFallbackPage' => true])
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a type="button" class="btn-sm rounded-xl btn" href="{{ route('monitors.edit', $monitor) }}#monitor-pages">{{ __('Abbrechen') }}</a>
                <input type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{ __('Speichern') }}"/>
            </div>
        </form>
    </x-bp.card>
</x-app-layout>
