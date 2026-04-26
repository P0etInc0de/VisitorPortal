@php
    use App\Models\Monitor;

    /** @var \App\Models\Monitor $monitor */
    /** @var \App\Models\MonitorSlide|null $slide */

    $backgroundPresets = Monitor::backgroundPresets();
    $currentSource = old('background_source', $slide?->resolvedBackgroundSource() ?? 'inherit');
    $defaultPreview = $monitor->backgroundAssetUrl();
    $uploadPreview = $slide?->resolvedBackgroundSource() === 'upload'
        ? $slide->backgroundAssetUrl($monitor)
        : $defaultPreview;

    $selectedPreview = match ($currentSource) {
        'preset-1', 'preset-2', 'preset-3' => asset($backgroundPresets[$currentSource]['path']),
        'upload' => $uploadPreview,
        default => $defaultPreview,
    };
@endphp

<section x-data="{ source: @js($currentSource) }" class="md:col-span-3 space-y-4 rounded-3xl border border-slate-200 bg-slate-50 p-5">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Seitenhintergrund') }}</h3>
        <p class="mt-1 text-sm text-slate-500">{{ __('Jede manuelle Monitor-Seite kann einen eigenen Hintergrund verwenden. Ohne Auswahl wird der Standard-Hintergrund aus den Monitor-Einstellungen benutzt.') }}</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        @foreach ([
            'inherit' => ['label' => __('Monitor-Standard'), 'description' => __('Verwendet den Standard-Hintergrund aus den Monitor-Einstellungen.'), 'preview' => $defaultPreview],
            'preset-1' => ['label' => $backgroundPresets['preset-1']['label'], 'description' => $backgroundPresets['preset-1']['description'], 'preview' => asset($backgroundPresets['preset-1']['path'])],
            'preset-2' => ['label' => $backgroundPresets['preset-2']['label'], 'description' => $backgroundPresets['preset-2']['description'], 'preview' => asset($backgroundPresets['preset-2']['path'])],
            'preset-3' => ['label' => $backgroundPresets['preset-3']['label'], 'description' => $backgroundPresets['preset-3']['description'], 'preview' => asset($backgroundPresets['preset-3']['path'])],
            'upload' => ['label' => __('Eigenes Bild'), 'description' => __('Lädt ein individuelles Bild für genau diese Seite hoch.'), 'preview' => $uploadPreview],
        ] as $source => $option)
            <label @class([
                'cursor-pointer overflow-hidden rounded-3xl border bg-white transition-all',
                'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' => $currentSource === $source,
                'border-slate-200 hover:border-slate-300' => $currentSource !== $source,
            ]) x-bind:class="source === '{{ $source }}' ? 'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="background_source" value="{{ $source }}" class="sr-only" x-model="source" @checked($currentSource === $source)>
                <div class="aspect-video w-full overflow-hidden bg-slate-100">
                    <img src="{{ $option['preview'] }}" alt="{{ $option['label'] }}" class="h-full w-full object-cover">
                </div>
                <div class="space-y-1 px-4 py-4">
                    <div class="font-semibold text-slate-900">{{ $option['label'] }}</div>
                    <p class="text-sm leading-6 text-slate-500">{{ $option['description'] }}</p>
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
                    <img src="{{ $selectedPreview }}" alt="{{ __('Seitenhintergrund Vorschau') }}" class="h-full w-full object-cover">
                </div>
            </div>
        </div>
    </div>
</section>
