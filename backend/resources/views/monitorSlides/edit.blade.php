<x-app-layout>
    <x-slot name="header">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
            {{ __('Seite bearbeiten') }}
        </h1>
    </x-slot>
    <x-bp.card>
        <form id="editMonitorSlide" method="POST" action="{{ route('monitors.slides.update',[$monitor, $slide]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid gap-4">
                <x-bp.input label="{{ __('Überschrift') }}" name="heading" :value="$slide->heading" class="h-11"/>
                <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" :value="$slide->subheading" class="h-11"/>

                @include('monitorSlides.partials.page-settings', [
                    'slideNumber' => $slide->slide_number,
                    'displayMode' => $slide->monitor_display_mode ?: ($monitor->monitor_display_mode ?: \App\Models\Monitor::DEFAULT_MONITOR_DISPLAY_MODE),
                    'toggleValues' => [
                        ['label' => __('Seite aktiv'), 'name' => 'is_active', 'checked' => $slide->is_active],
                        ['label' => __('Logo Anzeigen'), 'name' => 'show_logo', 'checked' => $slide->show_logo],
                        ['label' => __('Datum Anzeigen'), 'name' => 'show_date', 'checked' => $slide->show_date],
                    ],
                ])
                {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time" checked="{{$slide->show_time}}"/>--}}

                @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => $slide])

                @include('monitorSlides.partials.visitor-selection', [
                    'todayVisits' => $todayVisits,
                    'cancelUrl' => route('monitors.edit', $monitor).'#monitor-pages',
                    'submitLabel' => __('Speichern'),
                ])
            </div>
        </form>

    </x-bp.card>

</x-app-layout>

@include('monitorSlides.partials.visitor-selection-script', [
    'initialVisitors' => json_decode(old('visitors', 'null'), true) ?? $slide->visitors,
    'todayVisitors' => $todayVisitors,
])
