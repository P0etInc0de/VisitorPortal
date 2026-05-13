<?php

use App\Models\Monitor;
use App\Models\MonitorSlide;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Monitor $monitor;

    #[On('monitor-updated')]
    public function refreshSlides(): void {}

    public function with(): array
    {
        $this->monitor->refresh();

        $slides = $this->monitor->displaySlides()
            ->map(fn (MonitorSlide $slide): array => [
                'id' => (string) $slide->id,
                'heading' => $slide->heading,
                'subheading' => $slide->subheading,
                'show_logo' => (bool) $slide->show_logo,
                'show_date' => (bool) $slide->show_date,
                'visitors' => $slide->visitors ?? [],
                'background_asset' => $slide->backgroundAssetUrl($this->monitor),
            ]);

        if ($slides->isEmpty()) {
            $slides = collect([
                array_merge($this->monitor->fallbackSlideData(), [
                    'background_asset' => $this->monitor->fallbackBackgroundAssetUrl(),
                ]),
            ]);
        }

        return [
            'slides' => $slides,
            'slides_hash' => md5(json_encode([
                'monitor_updated_at' => optional($this->monitor->updated_at)?->timestamp,
                'slides' => $slides->toArray(),
            ])),
        ];
    }
};
?>
<div wire:poll.5s class="h-full w-full">
    <div id="monitor-slides-container"
         class="h-full w-full"
         wire:key="container-{{ $slides_hash }}"
         x-data="{
            current: 0,
            interval: null,

            get slideCount() {
                return this.$el.querySelectorAll('.slide').length;
            },

            init() {
                this.current = this.current % Math.max(this.slideCount, 1);
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
            $brandName = config('branding.name', 'VisitorPortal');
            $monitorLogoPath = config('branding.logo_light');
            $hasLogo = is_string($monitorLogoPath) && $monitorLogoPath !== '' && file_exists(public_path($monitorLogoPath));
            $cardStyle = $monitor->content_card_style ?: \App\Models\Monitor::DEFAULT_CONTENT_CARD_STYLE;
            $headerTextClasses = $monitor->header_text_is_light
                ? 'text-white drop-shadow-[0_8px_30px_rgba(15,23,42,0.4)]'
                : 'text-slate-900';
            $subheadingTextClasses = $monitor->header_text_is_light
                ? 'text-white/90 drop-shadow-[0_8px_30px_rgba(15,23,42,0.35)]'
                : 'text-[#1f2a44]/80';
            $contentCardClasses = match ($cardStyle) {
                'solid' => 'rounded-[2rem] border border-slate-200 bg-white/95 shadow-[0_18px_44px_rgba(15,23,42,0.08)]',
                'none' => 'rounded-[2rem] border border-transparent bg-transparent shadow-none',
                default => 'rounded-[2rem] border border-white/55 bg-white/62 shadow-[0_18px_44px_rgba(15,23,42,0.08)] backdrop-blur-[12px]',
            };
            $dateCardClasses = match ($cardStyle) {
                'solid' => 'inline-flex w-full flex-col rounded-[1.75rem] border border-slate-200 bg-white px-[1.4rem] py-[1.25rem] shadow-[0_18px_44px_rgba(15,23,42,0.08)] md:w-auto md:min-w-[10rem]',
                'none' => 'inline-flex w-full flex-col rounded-[1.75rem] border border-transparent bg-transparent px-0 py-0 shadow-none md:w-auto md:min-w-[10rem]',
                default => 'inline-flex w-full flex-col rounded-[1.75rem] border border-white/55 bg-white/62 px-[1.4rem] py-[1.25rem] shadow-[0_18px_44px_rgba(15,23,42,0.08)] backdrop-blur-[12px] md:w-auto md:min-w-[10rem]',
            };
        @endphp

        @foreach($slides as $slide)
            <div wire:key="slide-{{ $slide['id'] }}" class="slide h-full w-full" data-theme="light" x-show="current === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
                <main
                    class="relative h-full overflow-hidden bg-[#eef2f8] bg-cover bg-center bg-no-repeat"
                    @if ($slide['background_asset'])
                        style="background-image: url('{{ $slide['background_asset'] }}');"
                    @endif
                >
                    @if ($monitor->background_overlay_enabled)
                        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(248,250,252,0.72),rgba(241,245,249,0.48))]"></div>
                    @endif

                    <div class="relative z-10 mx-auto flex h-full w-full max-w-400 flex-col px-6 py-10 sm:px-14 md:px-10 xl:px-18">
                        <div class="flex items-start justify-between gap-8">
                            <div></div>
                            @if($slide['show_date'])
                                <div class="{{ $dateCardClasses }}">
                                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                                    <div class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 items-center justify-center py-6">
                            <div class="w-full max-w-[82rem]">
                                <div class="flex flex-col items-center justify-center gap-6 text-center sm:flex-row sm:items-center sm:justify-center sm:gap-8 sm:text-left lg:gap-10">
                                    @if ($slide['show_logo'] && $hasLogo)
                                        <img src="{{ asset($monitorLogoPath) }}"
                                             alt="{{ $brandName }}"
                                             width="1920"
                                             height="1080"
                                             loading="eager"
                                             decoding="sync"
                                             fetchpriority="high"
                                             style="aspect-ratio: 16 / 9;"
                                             class="max-h-28 w-auto object-contain sm:max-h-32 lg:max-h-36 {{ $monitor->header_text_is_light ? 'drop-shadow-[0_8px_30px_rgba(15,23,42,0.35)]' : '' }}">
                                    @endif

                                    <div>
                                        <h1 class="text-center font-bold leading-[0.95] tracking-[-0.06em] text-[clamp(3rem,2.5rem+2vw,4.9rem)] sm:text-left xl:text-[clamp(4.2rem,3.3rem+2.6vw,6.3rem)] {{ $headerTextClasses }}">{{ $slide['heading'] }}</h1>
                                        @if (filled($slide['subheading']))
                                            <h3 class="mx-auto mt-5 max-w-[58rem] text-center text-[clamp(1.2rem,1rem+0.55vw,1.7rem)] leading-[1.6] sm:mx-0 sm:text-left {{ $subheadingTextClasses }}">
                                                {{ $slide['subheading'] }}
                                            </h3>
                                        @endif
                                    </div>
                                </div>

                                @if (! empty($slide['visitors']))
                                    <div class="mt-12 grid content-center gap-5 lg:grid-cols-2">
                                        @foreach ($slide['visitors'] as $visitor)
                                            <article wire:key="visitor-{{ $slide['id'] }}-{{ $loop->index }}" class="grid w-full gap-4 p-6 {{ $contentCardClasses }} {{ $loop->last && $loop->count % 2 !== 0 ? 'lg:col-span-2 lg:justify-self-center lg:w-[calc(50%-0.625rem)]' : '' }}">
                                                <div class="flex h-full w-full items-center justify-center">
                                                    <h2 class="text-center text-[1.55rem] font-bold leading-[1.15] tracking-[-0.04em] md:text-[2.05rem]">{{ $visitor['name'] }}</h2>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        @endforeach
    </div>
</div>
