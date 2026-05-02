<x-app-layout>
    <x-slot name="header">
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
                    {{ __('Monitor Editor') }}
                </h1>
    </x-slot>

    <x-bp.alert/>

    {{-- Main Grid --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">

        {{-- LEFT AREA --}}
        <div class="space-y-6" id="monitor-pages">

            <x-bp.card title="{{ __('Monitor Seiten') }}" class="!mt-0">
                <livewire:monitor.index-monitor-slides :monitor="$monitor" />
            </x-bp.card>

        </div>
        {{-- RIGHT AREA --}}
        <aside class="space-y-6">
            <x-bp.card id="monitor-preview" title="{{ __('Vorschau') }}" class="!mt-0 p-4">
                <x-slot:actions>
                    <a
                        href="{{ route('monitors.show', $monitor) }}"
                        class="btn btn-outline btn-sm rounded-xl">
                        {{ __('Live-Anzeige öffnen') }}
                    </a>
                </x-slot:actions>

                <div class="px-1 pb-1">
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
                         class="relative w-full aspect-video overflow-hidden border border-base-300 isolate transform-gpu">

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

            <x-bp.card id="monitor-settings" title="{{__('Monitor Einstellungen')}}">
                <livewire:monitor.settings-form :monitor="$monitor" />
            </x-bp.card>
        </aside>
    </div>
</x-app-layout>
