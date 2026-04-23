<x-app-layout>
    <x-slot name="header">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Monitor Editor') }}
                </h1>
    </x-slot>

    {{-- Main Grid --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">

        {{-- LEFT AREA --}}
        <div>
            <x-bp.alert/>

            <x-bp.card title="{{ __('Monitor Seiten') }}">
                @if($monitor->auto_generation === 0)
                    <x-slot:actions>
                        <a type="button" href="{{route('monitors.slides.create', $monitor)}}" class="btn btn-primary btn-sm rounded-xl">{{__('Neue Seite')}}</a>
                    </x-slot:actions>
                @endif
                <livewire:monitor.index-monitor-slides :monitor="$monitor" />
            </x-bp.card>
            <x-bp.card title="{{__('Monitor Einstellungen')}}">
                <x-slot:actions>
                    <input type="submit" form="monitorSettings"  class="btn btn-primary btn-sm rounded-xl" value="{{__('Speichern')}}"/>
                </x-slot:actions>
                <form id="monitorSettings" method="POST" action="{{route('monitors.update', $monitor)}}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="md:grid-cols-2 grid gap-5">
                        <x-bp.input label="{{__('Übergangszeit (ms)')}}" value="{{$monitor->transition_time_milliseconds}}" name="transition_time_milliseconds"/>
                        <x-bp.toggle label="{{__('Seiten automatisch generieren')}}" name="auto_generation" checked="{{$monitor->auto_generation}}"/>
                    </div>
                    <x-bp.picture-upload label="{{__('Hintergrundbild auswählen')}}" name="image" src="{{$monitor->image_path}}" form_id="monitorSettings" id="picture-upload"/>
                </form>
            </x-bp.card>

        </div>
            {{-- RIGHT AREA --}}
        <aside class="xl:sticky xl:top-6 xl:self-start">
            <x-bp.card title="{{ __('Vorschau') }}">
                <div class="p-5">
                    <div id="preview-container"
                         x-data="{
                     scale: 1,
                     updateScale() {
                         // Scale = Container Width / Native Content Width
                         this.scale = this.$el.clientWidth / this.$refs.content.offsetWidth;
                     }
                 }"
                         x-init="
                     updateScale();
                     new ResizeObserver(() => updateScale()).observe($el);
                 "
                         {{-- Fix hinzugefügt: isolate und transform-gpu erzwingen sauberes Clipping der Ecken --}}
                         class="relative w-full aspect-video overflow-hidden border border-slate-200 isolate transform-gpu">

                        <div id="preview-content"
                             x-ref="content"
                             :style="`transform: scale(${scale});`"
                             {{-- Nutze w-[1920px] h-[1080px] falls w-480/h-270 nicht explizit in deiner Config stehen! --}}
                             class="absolute left-0 top-0 w-480 h-270 origin-top-left transition-transform duration-75">

                            <livewire:monitor.monitor-slides :monitor="$monitor"/>

                        </div>
                    </div>
                </div>
            </x-bp.card>

            <div class="mt-3 flex justify-end w-full">
                <a
                    href="{{ route('monitors.show', $monitor) }}"
                    class=" btn btn-outline rounded-xl">
                    {{ __('Live-Anzeige öffnen') }}
                </a>
            </div>
        </aside>
    </div>
</x-app-layout>
