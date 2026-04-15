<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Monitor Editor') }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('monitors.show', $monitor) }}"
                    class="btn btn-outline rounded-xl">
                    {{ __('Live-Anzeige öffnen') }}
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Main Grid --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">

        {{-- LEFT AREA --}}
        <div class="space-y-6">
            <div class="bp-page">
                <div class="bp-content">

                    <x-bp.alert/>

                    <x-bp.card title="{{ __('Monitor Seiten') }}">
                        <x-slot:actions>
                            <a type="button" href="{{route('monitor_slides.create')}}" class="btn btn-primary btn-sm rounded-xl">{{__('Neue Seite')}}</a>
                        </x-slot:actions>
                        <div class="bp-visit-item">
                        @forelse ($monitor_slides as $monitor_slide)


                                {{-- Slide Number --}}
                                <div class="bp-visit-timebox">
                                    <span class="bp-visit-time">{{ $monitor_slide->slide_number }}</span>
                                </div>

                                {{-- Main Info --}}
                                <div class="bp-visit-main">
                                    <div class="bp-visit-title">{{ $monitor_slide->heading }}</div>
                                    <div class="bp-visit-company">{{ $monitor_slide->subheading }}</div>
                                    <div class="bp-visit-meta">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($monitor_slide->visitors as $visitor)
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-outline rounded-full"
                                                >
                                                   {{ $visitor['name'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{--- Delete Button ---}}
                                <div>
                                    <form id="delete-button-{{$monitor_slide->id}}" method="POST" action="{{ route('monitor_slides.destroy', $monitor_slide) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <form id="edit-button-{{$monitor_slide->id}}" method="GET" action="{{ route('monitor_slides.edit', $monitor_slide) }}">
                                        @csrf
                                    </form>
                                    <div class="form-group">
                                        <input form="edit-button-{{$monitor_slide->id}}" type="submit" class="btn-sm rounded-xl btn" value="{{ __('Bearbeiten') }}">
                                        <input form="delete-button-{{$monitor_slide->id}}" type="submit" class="btn-sm rounded-xl btn" value="{{ __('Löschen') }}" onclick="return confirm('{{ __('Sicher löschen?') }}')">
                                    </div>
                                </div>



                                @empty
                                    <div class="py-12 text-center">
                                        <p class="text-slate-400">{{ __('Kein Seiten gefunden.') }}</p>
                                    </div>
                            </div>
                        @endforelse
                    </x-bp.card>
                    <x-bp.card title="{{__('Monitor Einstellungen')}}">
                        <x-slot:actions>
                            <input type="submit" form="monitorSettings"  class="btn btn-primary btn-sm rounded-xl" value="{{__('Speichern')}}"/>
                        </x-slot:actions>
                        <form id="monitorSettings" method="POST" action="{{route('monitors.update', $monitor)}}">
                            @csrf
                            @method('PUT')
                            <x-bp.input label="{{__('Übergangszeit (ms)')}}" value="{{$monitor->transition_time_milliseconds}}" name="transition_time_milliseconds"></x-bp.input>
                        </form>
                    </x-bp.card>
                </div>
            </div>
        </div>
            {{-- RIGHT AREA --}}
        <aside class="xl:sticky xl:top-6 xl:self-start">
            <x-bp.card title="{{ __('Vorschau') }}">

                <div class="p-5">
                    <div id="preview-container" class="relative w-full aspect-[16/10] overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-100">
                        <div id="preview-content" class="absolute left-0 top-0 w-[1920px] h-[1080px] origin-top-left transition-transform duration-75">
                            <x-monitor.show-monitor :monitor="$monitor" :slides="$monitor_slides"/>
                        </div>
                    </div>
                </div>
            </x-bp.card>
        </aside>

    </div>

</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('preview-container');
        const content = document.getElementById('preview-content');

        if (!container || !content) return;

        const scalePreview = () => {
            const scale = container.clientWidth / 1920;
            content.style.transform = `scale(${scale})`;
        };

        scalePreview();

        window.addEventListener('resize', scalePreview);
    });
</script>
