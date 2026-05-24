@php
    $defaultHeading = config('branding.monitor_slide_heading', 'Welcome!');
    $defaultSubheading = config('branding.monitor_fallback_subheading', "We're glad you're here.");
@endphp

<x-app-layout>
    <x-slot name="header">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
            {{ __('Neue Seite erstellen') }}
        </h1>
    </x-slot>
        <x-bp.card title="{{ __('Neue Seite') }}">
            <form id="createMonitorSlide" method="POST" action="{{ route('monitors.slides.store', [$monitor]) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid gap-4">
                    <x-bp.input label="{{ __('Überschrift') }}" name="heading" :value="$defaultHeading" class="h-11"/>
                    <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" :value="$defaultSubheading" class="h-11"/>

                    @include('monitorSlides.partials.page-settings', [
                        'slideNumber' => $defaultSlideNumber,
                        'displayMode' => $monitor->monitor_display_mode ?: \App\Models\Monitor::DEFAULT_MONITOR_DISPLAY_MODE,
                        'toggleValues' => [
                            ['label' => __('Seite aktiv'), 'name' => 'is_active', 'checked' => 1],
                            ['label' => __('Logo Anzeigen'), 'name' => 'show_logo', 'checked' => 1],
                            ['label' => __('Datum Anzeigen'), 'name' => 'show_date', 'checked' => 1],
                        ],
                    ])
                    {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time"/>--}}

                    @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null])

                    @include('monitorSlides.partials.visitor-selection', [
                        'todayVisits' => $todayVisits,
                        'cancelUrl' => route('monitors.edit', $monitor).'#monitor-pages',
                        'submitLabel' => __('Speichern'),
                    ])

                    <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
                </div>
            </form>
        </x-bp.card>
</x-app-layout>

@include('monitorSlides.partials.visitor-selection-script', [
    'initialVisitors' => json_decode(old('visitors', '[]'), true) ?? [],
    'todayVisitors' => $todayVisitors,
])
