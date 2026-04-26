@php
    use App\Models\Monitor;

    /** @var \App\Models\Monitor $monitor */
    /** @var \App\Models\MonitorSlide|null $slide */

    $isFallbackPage = $isFallbackPage ?? false;

    $backgroundPresets = Monitor::backgroundPresets();
    $currentSource = old('background_source', $isFallbackPage
        ? ($monitor->fallbackResolvedBackgroundSource() ?? 'inherit')
        : ($slide?->resolvedBackgroundSource() ?? 'inherit'));
    $defaultPreview = $monitor->backgroundAssetUrl();
    $uploadPreview = $isFallbackPage
        ? ($monitor->fallbackResolvedBackgroundSource() === 'upload'
            ? $monitor->fallbackBackgroundAssetUrl()
            : null)
        : ($slide?->resolvedBackgroundSource() === 'upload'
            ? $slide->backgroundAssetUrl($monitor)
            : null);

    $selectedPreview = match ($currentSource) {
        'preset-1', 'preset-2', 'preset-3' => asset($backgroundPresets[$currentSource]['path']),
        'upload' => $uploadPreview,
        default => $defaultPreview,
    };
@endphp

<section x-data="{ source: @js($currentSource) }" class="md:col-span-3 space-y-4 rounded-3xl border border-slate-200 bg-slate-50 p-5">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Seitenhintergrund') }}</h3>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ([
            'inherit' => ['label' => __('Monitor-Standard'), 'preview' => $defaultPreview],
            'preset-1' => ['label' => $backgroundPresets['preset-1']['label'], 'preview' => asset($backgroundPresets['preset-1']['path'])],
            'preset-2' => ['label' => $backgroundPresets['preset-2']['label'], 'preview' => asset($backgroundPresets['preset-2']['path'])],
            'preset-3' => ['label' => $backgroundPresets['preset-3']['label'], 'preview' => asset($backgroundPresets['preset-3']['path'])],
            'upload' => ['label' => __('Eigenes Bild'), 'preview' => $uploadPreview],
        ] as $source => $option)
            @php $isSelected = $currentSource === $source; @endphp
            <label @class([
                'cursor-pointer overflow-hidden rounded-3xl border bg-white transition-all',
                'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' => $isSelected,
                'border-slate-200 hover:border-slate-300' => ! $isSelected,
            ]) x-bind:class="source === '{{ $source }}' ? 'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="background_source" value="{{ $source }}" class="sr-only" x-model="source" @checked($currentSource === $source)>
                <div class="aspect-video w-full overflow-hidden bg-slate-100">
                    @if ($source === 'upload' && blank($option['preview']))
                        <div class="flex h-full items-center justify-center px-6 text-center text-sm text-slate-500">
                            {{ __('Eigenes Bild hochladen') }}
                        </div>
                    @else
                        <img src="{{ $option['preview'] }}" alt="{{ $option['label'] }}" class="h-full w-full object-cover">
                    @endif
                </div>
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-xl font-semibold text-slate-900">{{ $option['label'] }}</div>
                        @if ($isSelected)
                            <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ __('Aktiv') }}</span>
                        @endif
                    </div>
                </div>
            </label>
        @endforeach
    </div>

    @error('background_source')
        <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
    @enderror

    <div x-show="source === 'upload'" x-transition class="rounded-3xl border border-slate-200 bg-white p-5">
        <div>
            <h4 class="text-sm font-semibold text-slate-900">{{ __('Eigenes Hintergrundbild für diese Seite') }}</h4>
            <p class="mt-1 text-sm text-slate-500">{{ __('Empfohlen: 1920x1080 oder größer im Format 16:9. Neue Uploads werden nach dem Speichern in der Monitor-Vorschau sichtbar.') }}</p>
        </div>

        <div class="mt-4 space-y-4">
            <div>
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="file-input file-input-bordered w-full rounded-xl border-slate-300 bg-white">
                @error('image')
                    <span class="mt-2 block text-sm font-medium text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="aspect-video w-full overflow-hidden bg-slate-100">
                    @if (filled($selectedPreview))
                        <img src="{{ $selectedPreview }}" alt="{{ __('Seitenhintergrund Vorschau') }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full items-center justify-center px-6 text-center text-sm text-slate-500">
                            {{ __('Die Vorschau erscheint nach dem Hochladen eines eigenen Bildes.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
