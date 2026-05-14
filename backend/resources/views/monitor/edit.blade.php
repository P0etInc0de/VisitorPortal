@php
    $preloadBackground = $monitor->firstDisplayBackgroundAssetUrl();
    $monitorLogoPath = config('branding.logo_light');
    $hasMonitorLogo = is_string($monitorLogoPath) && $monitorLogoPath !== '' && file_exists(public_path($monitorLogoPath));
@endphp

<x-app-layout>
    <x-slot name="head">
        @if ($preloadBackground)
            <link rel="preload" as="image" href="{{ $preloadBackground }}" fetchpriority="high">
        @endif
        @if ($hasMonitorLogo)
            <link rel="preload" as="image" href="{{ asset($monitorLogoPath) }}" fetchpriority="high">
        @endif
    </x-slot>

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
                    <div
                        id="preview-container"
                        x-data="{
                            scale: 0.44,
                            ready: false,
                            observer: null,
                            updateScale() {
                                if (!this.$refs || !this.$refs.content) {
                                    return;
                                }

                                this.scale = this.$el.clientWidth / 1920;
                                this.ready = true;
                            },
                            init() {
                                this.updateScale();
                                this.$nextTick(() => this.updateScale());
                                this.observer = new ResizeObserver(() => this.updateScale());
                                this.observer.observe(this.$el);
                            },
                            destroy() {
                                if (this.observer) {
                                    this.observer.disconnect();
                                }
                            }
                        }"
                        class="relative w-full aspect-video overflow-hidden border border-base-300 bg-base-200 isolate transform-gpu"
                    >
                        <div
                            id="preview-content"
                            x-ref="content"
                            x-cloak
                            x-show="ready"
                            :style="{ width: '1920px', height: '1080px', transform: `scale(${scale})` }"
                            class="absolute left-0 top-0 origin-top-left"
                        >
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
