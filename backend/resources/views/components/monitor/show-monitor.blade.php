<?php
$hasLogo = file_exists(public_path('images/logo.png'));
$hasCustomBackground = file_exists(public_path('images/monitor-background.jpg')) || file_exists(public_path('images/monitor-background.png'));
$backgroundAsset = file_exists(public_path('images/monitor-background.jpg'))
    ? asset('images/monitor-background.jpg')
    : (file_exists(public_path('images/monitor-background.png')) ? asset('images/monitor-background.png') : null);
?>

@props(['monitor', 'slides'])
@foreach($slides as $slide)
    <div class="slide" style="display: none" id="slide-{{ $slide->id }}">
        <main class="bp-monitor-display {{ $hasCustomBackground ? 'bp-monitor-display-with-image' : 'bp-monitor-display-default' }}"
              @if ($backgroundAsset)
                  style="background-image: linear-gradient(135deg, rgba(243,245,249,0.78), rgba(255,255,255,0.62)), url('{{ $backgroundAsset }}');"
            @endif
        >
            <div class="bp-monitor-overlay"></div>

            <div class="mt-5 relative z-10 mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-10 py-10 sm:px-14 xl:px-18">
                <div class="flex items-start justify-between gap-8">
                    <div class="flex items-center gap-6">
                        @if ($slide->show_logo)
                            <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" class="max-h-18 w-auto object-contain">
                        @endif
                    </div>
                    @if($slide->show_date)
                        <div class="bp-monitor-chip">
                            <div class="text-xs font-semibold uppercase tracking-[0.28em] text-base-content/45">{{ __('Heute') }}</div>
                            <div class="mt-3 text-3xl font-semibold tracking-tight">{{ now()->format('d.m.Y') }}</div>
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
                            <article class="bp-monitor-visit-card
                                {{-- Is last element and uneven? --}}
                                {{ $loop->last && $loop->count % 2 !== 0 ? 'lg:col-span-2 lg:justify-self-center lg:w-[calc(50%-0.625rem)]' : 'w-full' }}
                            ">
                                <div class="flex h-full w-full items-center justify-center">
                                    <h2 class="bp-monitor-visit-name text-center">{{ $visitor["name"] }}</h2>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const slides = @js($slides);

        if (slides.length === 0) return;

        let current = 0;

        function showSlide(index) {
            const element = document.getElementById('slide-' + slides[index].id);
            if (element) element.style.display = "block";
        }

        function hideSlide(index) {
            const element = document.getElementById('slide-' + slides[index].id);
            if (element) element.style.display = "none";
        }

        showSlide(current);

        if (slides.length > 1) {
            setInterval(() => {
                hideSlide(current);
                current = (current + 1) % slides.length;
                showSlide(current);
            }, {{$monitor->transition_time_milliseconds}});
        }
    });
</script>




