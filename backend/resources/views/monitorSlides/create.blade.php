<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Neue Seite erstellen') }}
                </h1>
            </div>
        </div>
    </x-slot>
    <div class="grid gap-6">
    <x-bp.card title="{{ __('Neue Seite') }}">

        <x-slot:actions>
            <input form="createMonitorSlide" type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{__('Speichern')}}"/>
        </x-slot:actions>

        <form id="createMonitorSlide" method="POST" action="{{ route('monitor_slides.store') }}">
            @csrf
            <div class="grid gap-5 md:grid-cols-3">
                <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{ __('Herzlich willkommen') }}!"/>
                <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading"/>
                <x-bp.input label="{{ __('Seitennummer') }}" name="slide_number" value="{{ $defaultSlideNumber }}"/>

                <x-bp.toggle label="{{ __('Logo Anzeigen') }}" name="show_logo"/>
                <x-bp.toggle label="{{ __('Datum Anzeigen') }}" name="show_date"/>
                <x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time"/>

                <x-bp.multiselect label="Select Tags" name="tags" id="tags" placeholder="Search and select tags...">
                    @foreach($availableTags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->first_name }}</option>
                    @endforeach
                </x-bp.multiselect>



                <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
            </div>
        </form>

    </x-bp.card>

    <x-bp.card title="{{ __('Heutige Besuche') }}">
        <div class="space-y-4">
            @foreach ($todayVisits as $visit)
                <article class="rounded-[1.5rem] border border-base-300 bg-base-100 px-5 py-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex gap-4">
                            <div class="min-w-[72px] shrink-0">
                                <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">
                                    {{ \Carbon\Carbon::parse($visit->scheduled_from)->format('H:i') }}
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="text-xl font-semibold tracking-tight text-base-content">
                                    {{-- //$visit->title --}}
                                    Titel
                                </div>

                                <div class="mt-1 text-lg text-base-content/75">
                                    {{ $visit->company }}
                                    Firma
                                </div>
                            </div>
                            <div class="min-w-20">

                            </div>
                            <div class="min-w-20">
                                @foreach($visit->visitors as $visitors)
                                    {{ $visitors->first_name }} {{ $visitors->last_name }};
                                @endforeach

                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                        <span class="badge {{ $visit['statusClass'] }} rounded-full px-3 py-3 text-sm font-semibold">
                                            {{ $visit['status'] }}
                                        </span>

                            <div class="flex flex-wrap gap-2">
                              {{-- Platz für ein Button --}}
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

    </x-bp.card>
</div>
</x-app-layout>

