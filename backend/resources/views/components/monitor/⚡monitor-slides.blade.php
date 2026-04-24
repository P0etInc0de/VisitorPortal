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
                    class="relative h-full overflow-hidden bg-cover bg-center bg-no-repeat {{ $hasCustomBackground ? 'bg-[#eef2f8]' : 'bg-[radial-gradient(circle_at_top_left,rgba(79,70,229,0.10),transparent_35%),linear-gradient(180deg,#f7f8fc_0%,#eef2f8_100%)]' }}"
                    @if ($backgroundAsset)
                        style="background-image:
                        linear-gradient(135deg, rgba(20,20,20,0.45), rgba(20,20,20,0.25)),
                        url('{{ $backgroundAsset }}');"
                    @endif
                >
                    <div class="absolute inset-0 bg-[linear-gradient(140deg,rgba(255,255,255,0.60),rgba(243,245,249,0.78))] backdrop-blur-[2px]"></div>

                    <div
                        class="relative z-10 mx-auto mt-5 flex h-full w-full max-w-400 flex-col px-5 py-10 sm:px-14 md:px-10 xl:px-18">
                        <div class="flex items-start justify-between gap-8">
                            <div class="flex items-center gap-6">
                                @if ($slide->show_logo)
                                    <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}"
                                         class="max-h-18 w-auto object-contain">
                                @endif
                            </div>
                            @if($slide->show_date)
                                <div class="inline-flex w-full flex-col rounded-[1.75rem] border border-white/55 bg-white/68 px-[1.4rem] py-[1.25rem] shadow-[0_18px_44px_rgba(15,23,42,0.08)] backdrop-blur-[12px] md:w-auto md:min-w-[10rem]">
                                    <div
                                        class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                                    <div
                                        class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-14 flex flex-1 flex-col">
                            <div class="mb-10 w-full">
                                <h1 class="text-center font-bold leading-[0.95] tracking-[-0.06em] text-[clamp(3rem,2.5rem+2vw,4.6rem)] xl:text-[clamp(4rem,3.1rem+2.6vw,6rem)]">{{ $slide->heading }}</h1>
                                <h3 class="mx-auto mt-6 max-w-[52rem] text-center text-[clamp(1.2rem,1rem+0.55vw,1.65rem)] leading-[1.65] text-[#1f2a44]/78">
                                    {{ $slide->subheading }}
                                </h3>
                            </div>
                            <div class="grid flex-1 content-center gap-5 lg:grid-cols-2">
                                @foreach ($slide->visitors as $visitor)
                                    <article wire:key="visitor-{{ $slide->id }}-{{ $loop->index }}"
                                             class="grid w-full gap-4 rounded-[2rem] border border-[#0f172a]/8 bg-white/82 p-6 shadow-[0_8px_20px_rgba(15,23,42,0.04)] backdrop-blur-[10px] {{ $loop->last && $loop->count % 2 !== 0 ? 'lg:col-span-2 lg:justify-self-center lg:w-[calc(50%-0.625rem)]' : '' }}">
                                        <div class="flex h-full w-full items-center justify-center">
                                            <h2 class="text-center text-[1.5rem] font-bold leading-[1.1] tracking-[-0.04em] md:text-[2rem]">{{ $visitor['name'] }}</h2>
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
