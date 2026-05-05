@props(['title' => null, 'subtitle' => null])
<section {{ $attributes->merge(['class' => 'mt-3 rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm']) }}>
    @if($title || $subtitle || isset($actions))
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                @if($title)
                    <h2 class="text-xl font-semibold text-base-content">{{ $title }}</h2>
                @endif
                @if($subtitle)
                        <p class="mt-1 text-sm text-base-content/65">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif
    {{ $slot }}
</section>
