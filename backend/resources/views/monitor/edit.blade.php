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
                <x-slot:actions>
                    <a type="button" href="{{route('monitors.slides.create', $monitor)}}" class="btn btn-primary btn-sm rounded-xl">{{__('Neue Seite')}}</a>
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


                        <div class="form-group flex gap-2">
                            {{--- delete and edit button ---}}
                            @if($monitor->auto_generation === 0)
                                <a href="{{ route('monitors.slides.edit', [$monitor->id, $monitor_slide->id]) }}" class="btn-sm rounded-xl btn">
                                    {{ __('Bearbeiten') }}
                                </a>
                                <form method="POST" action="{{ route('monitors.slides.destroy', [$monitor->id, $monitor_slide->id]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm rounded-xl btn" onclick="return confirm('{{ __('Sicher löschen?') }}')">
                                        {{ __('Löschen') }}
                                    </button>
                                </form>
                            @endif
                        </div>



                        @empty
                            <div class="py-12 text-center">
                                <p class="text-slate-400">{{ __('Kein Seiten gefunden.') }}</p>
                            </div>
                   @endforelse
                </div>
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
                         class="relative w-full aspect-video overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-100">

                        <div id="preview-content"
                             x-ref="content"
                             :style="`transform: scale(${scale});`"
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
