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

@endphp

<section
    x-data="{
        source: @js($currentSource),
        initialUploadPreview: @js($uploadPreview),
        uploadPreview: @js($uploadPreview),
        uploadObjectUrl: null,
        updateUploadPreview(event) {
            const file = event.target.files?.[0];

            if (this.uploadObjectUrl) {
                URL.revokeObjectURL(this.uploadObjectUrl);
                this.uploadObjectUrl = null;
            }

            if (! file) {
                this.uploadPreview = this.initialUploadPreview;
                return;
            }

            this.uploadObjectUrl = URL.createObjectURL(file);
            this.uploadPreview = this.uploadObjectUrl;
        },
    }"
    class="space-y-3 rounded-3xl border border-base-300 bg-base-200 p-4"
>
    <div>
        <h3 class="text-sm font-semibold text-base-content">{{ __('Seitenhintergrund') }}</h3>
    </div>

    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(13rem,1fr))]">
        @foreach ([
            'inherit' => ['label' => __('Monitor-Standard'), 'preview' => $defaultPreview],
            'preset-1' => ['label' => $backgroundPresets['preset-1']['label'], 'preview' => asset($backgroundPresets['preset-1']['path'])],
            'preset-2' => ['label' => $backgroundPresets['preset-2']['label'], 'preview' => asset($backgroundPresets['preset-2']['path'])],
            'preset-3' => ['label' => $backgroundPresets['preset-3']['label'], 'preview' => asset($backgroundPresets['preset-3']['path'])],
            'upload' => ['label' => __('Eigenes Bild'), 'preview' => $uploadPreview],
        ] as $source => $option)
            @php $isSelected = $currentSource === $source; @endphp
            <label @class([
                'cursor-pointer overflow-hidden rounded-[1.5rem] border bg-base-100 transition-all',
                'border-primary ring-1 ring-primary/20 shadow-sm' => $isSelected,
                'border-base-300 hover:border-base-content/20' => ! $isSelected,
            ]) x-bind:class="source === '{{ $source }}' ? 'border-primary ring-1 ring-primary/20 shadow-sm' : 'border-base-300 hover:border-base-content/20'">
                <input type="radio" name="background_source" value="{{ $source }}" class="sr-only" x-model="source" @checked($currentSource === $source)>
                <div class="aspect-video w-full overflow-hidden bg-base-200">
                        @if ($source === 'upload')
                            <template x-if="uploadPreview">
                                <img :src="uploadPreview" alt="{{ $option['label'] }}" class="h-full w-full object-cover">
                            </template>
                            <template x-if="! uploadPreview">
                                <div class="flex h-full items-center justify-center px-6 text-center text-sm text-base-content/65">
                                    {{ __('Eigenes Bild hochladen') }}
                                </div>
                            </template>
                        @else
                            <img src="{{ $option['preview'] }}" alt="{{ $option['label'] }}" class="h-full w-full object-cover">
                        @endif
                    </div>
                <div class="px-3 py-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold text-base-content">{{ $option['label'] }}</div>
                        <span x-show="source === '{{ $source }}'" x-cloak class="shrink-0 rounded-full bg-primary/12 px-2 py-0.5 text-[11px] font-semibold text-primary">{{ __('Aktiv') }}</span>
                    </div>
                </div>
            </label>
        @endforeach

        <div x-show="source === 'upload'" x-cloak x-transition class="rounded-[1.5rem] border border-base-300 bg-base-100 p-4 md:col-span-2">
            <div>
                <h4 class="text-sm font-semibold text-base-content">{{ __('Eigenes Hintergrundbild für diese Seite') }}</h4>
                <p class="mt-1 text-sm text-base-content/65">{{ __('Empfohlen: 1920x1080 oder größer im Format 16:9. Neue Uploads werden nach dem Speichern in der Monitor-Vorschau sichtbar.') }}</p>
            </div>

            <div class="mt-3">
                <input type="file" name="image" accept="image/png,image/jpeg,image/webp" @change="updateUploadPreview($event)" class="file-input file-input-bordered w-full rounded-xl border-base-300 bg-base-100">
                @error('image')
                    <span class="mt-2 block text-sm font-medium text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    @error('background_source')
        <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
    @enderror

</section>
