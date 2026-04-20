<?php

use Illuminate\Support\Collection;
use Livewire\Component;
use App\Models\Monitor;

new class extends Component {
    public Monitor $monitor;

    public function with(): array
    {
        $this->monitor->refresh();
        $slides = $this->monitor->monitorSlides()->get();
        // Livewire re-runs this during the poll
        return [
            'slides' => $slides,
            'slides_hash' => md5($slides->max('updated_at').$this->monitor->updated_at),
        ];
    }
};
?>
<div wire:poll.5s class="h-full w-full">
    <div id="monitor-slides-container"
         class="h-full w-full"
         wire:key="container-{{ $slides_hash }}"

         {{-- Alpine.js handles the slideshow state --}}
         x-data="{
            current: 0,
            interval: null,


            get slideCount() {
                return document.querySelectorAll('#monitor-slides-container .slide').length;
            },

            init() {
                this.current = this.current % this.slideCount;
                if (this.interval) clearInterval(this.interval);

                this.interval = setInterval(() => {
                    if (this.slideCount > 1) {
                        this.current = (this.current + 1) % this.slideCount;
                    } else {
                        this.current = 0;
                    }
                }, {{ $monitor->transition_time_milliseconds ?? 5000 }});
            }
         }">
        @php
            $hasLogo = file_exists(public_path('images/logo.png'));
            $hasCustomBackground = !empty($monitor->image_path) && Storage::disk('public')->exists($monitor->image_path);
            $backgroundAsset = $hasCustomBackground
                ? asset('storage/'.$monitor->image_path)
                : null;


        @endphp

        @foreach($slides as $index => $slide)
            <div wire:key="slide-{{ $slide->id }}"
                 class="slide h-full w-full"
                 x-show="current === {{ $loop->index }}"
            >

                <main
                    class="bp-monitor-display h-full {{ $hasCustomBackground ? 'bp-monitor-display-with-image' : 'bp-monitor-display-default' }}"
                    @if ($backgroundAsset)
                        style="background-image:
                        linear-gradient(135deg, rgba(20,20,20,0.45), rgba(20,20,20,0.25)),
                        url('{{ $backgroundAsset }}');"
                    @endif
                >
                    <div class="bp-monitor-overlay"></div>

                    <div
                        class="mt-5 relative z-10 mx-auto flex h-full w-full max-w-400 flex-col px-10 py-10 sm:px-14 xl:px-18">
                        <div class="flex items-start justify-between gap-8">
                            <div class="flex items-center gap-6">
                                @if ($slide->show_logo)
                                    <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}"
                                         class="max-h-18 w-auto object-contain">
                                @endif
                            </div>
                            @if($slide->show_date)
                                <div class="bp-monitor-chip">
                                    <div
                                        class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                                    <div
                                        class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-14 flex flex-1 flex-col">
                            <div class="mb-10 w-full">
                                <h1 class="bp-monitor-title text-center">{{ $slide->heading }}</h1>
                                <h3 class="bp-monitor-subtitle text-center">
                                    {{ $slide->subheading }}
                                </h3>
                            </div>
                            <div class="grid flex-1 content-center gap-5 lg:grid-cols-2">
                                @foreach ($slide->visitors as $visitor)
                                    <article wire:key="visitor-{{ $slide->id }}-{{ $loop->index }}"
                                             class="bp-monitor-visit-card
                                        {{ $loop->last && $loop->count % 2 !== 0 ? 'lg:col-span-2 lg:justify-self-center lg:w-[calc(50%-0.625rem)]' : 'w-full' }}
                                    ">
                                        <div class="flex h-full w-full items-center justify-center">
                                            <h2 class="bp-monitor-visit-name text-center">{{ $visitor['name'] }}</h2>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        @endforeach
    </div>
</div>
