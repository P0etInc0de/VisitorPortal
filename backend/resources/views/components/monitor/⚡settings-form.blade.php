<?php

use App\Models\Monitor;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Monitor $monitor;

    public int $transitionTimeSeconds = 5;

    public bool $autoGeneration = true;

    public int $autoGenerationWindowMinutes = 30;

    public string $backgroundSource = Monitor::DEFAULT_BACKGROUND_SOURCE;

    public bool $backgroundOverlayEnabled = false;

    public bool $headerTextIsLight = false;

    public string $contentCardStyle = Monitor::DEFAULT_CONTENT_CARD_STYLE;

    public $image = null;

    public ?string $existingImagePath = null;

    public bool $removeExistingImage = false;

    public bool $isDirty = false;

    /**
     * @var array<string, mixed>
     */
    public array $baseline = [];

    public function mount(Monitor $monitor): void
    {
        $this->monitor = $monitor;
        $this->syncFromMonitor();
    }

    public function updated(string $property): void
    {
        if ($property === 'backgroundSource' && $this->backgroundSource !== 'upload') {
            $this->image = null;
        }

        if ($property === 'image' && $this->image !== null) {
            $this->backgroundSource = 'upload';
            $this->removeExistingImage = false;
            $this->resetErrorBag('image');
        }

        $this->refreshDirtyState();
    }

    public function removeUploadedImage(): void
    {
        $this->image = null;

        if (filled($this->existingImagePath)) {
            $this->removeExistingImage = true;
        }

        if ($this->backgroundSource === 'upload') {
            $this->backgroundSource = Monitor::DEFAULT_BACKGROUND_SOURCE;
        }

        $this->refreshDirtyState();
    }

    public function resetToDefaults(): void
    {
        $defaults = Monitor::defaultSettings();

        $this->transitionTimeSeconds = (int) ($defaults['transition_time_milliseconds'] / 1000);
        $this->autoGeneration = (bool) $defaults['auto_generation'];
        $this->autoGenerationWindowMinutes = (int) $defaults['auto_generation_window_minutes'];
        $this->backgroundSource = (string) $defaults['background_source'];
        $this->backgroundOverlayEnabled = (bool) $defaults['background_overlay_enabled'];
        $this->headerTextIsLight = (bool) $defaults['header_text_is_light'];
        $this->contentCardStyle = (string) $defaults['content_card_style'];
        $this->image = null;
        $this->removeExistingImage = filled($this->existingImagePath);
        $this->resetErrorBag();

        $this->refreshDirtyState();
    }

    public function save(): void
    {
        if (! $this->isDirty) {
            return;
        }

        $validated = $this->validate([
            'transitionTimeSeconds' => ['required', 'integer', 'between:1,50'],
            'autoGeneration' => ['boolean'],
            'autoGenerationWindowMinutes' => ['required', 'integer', 'between:1,180'],
            'backgroundSource' => ['required', 'in:preset-1,preset-2,preset-3,upload'],
            'backgroundOverlayEnabled' => ['boolean'],
            'headerTextIsLight' => ['boolean'],
            'contentCardStyle' => ['required', 'in:solid,transparent,none'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:20000'],
        ]);

        if (
            $validated['backgroundSource'] === 'upload'
            && $this->image === null
            && blank($this->existingImagePath)
            && ! $this->removeExistingImage
        ) {
            $this->addError('image', __('Bitte wählen Sie zuerst ein eigenes Hintergrundbild aus.'));

            return;
        }

        $imagePath = $this->existingImagePath;

        if ($this->removeExistingImage && filled($imagePath)) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($this->image !== null) {
            if (filled($this->existingImagePath)) {
                Storage::disk('public')->delete($this->existingImagePath);
            }

            $imagePath = $this->image->store('images', 'public');
            $validated['backgroundSource'] = 'upload';
        }

        $this->monitor->update([
            'transition_time_milliseconds' => $validated['transitionTimeSeconds'] * 1000,
            'auto_generation' => $validated['autoGeneration'],
            'auto_generation_window_minutes' => $validated['autoGenerationWindowMinutes'],
            'background_source' => $validated['backgroundSource'],
            'background_overlay_enabled' => $validated['backgroundOverlayEnabled'],
            'header_text_is_light' => $validated['headerTextIsLight'],
            'content_card_style' => $validated['contentCardStyle'],
            'image_path' => $imagePath,
        ]);

        Log::info('User ID '.auth()->id()." updated settings for Monitor ID: {$this->monitor->id}");

        if ($this->monitor->auto_generation) {
            app(WelcomeMonitorAutoGeneration::class)();
        } else {
            $this->monitor->monitorSlides()
                ->where('is_auto_generated', true)
                ->delete();
        }

        $this->monitor->refresh();
        $this->syncFromMonitor();

        $this->dispatch('monitor-updated');
        $this->dispatch('monitor-settings-saved');
    }

    protected function syncFromMonitor(): void
    {
        $this->monitor->refresh();
        $this->transitionTimeSeconds = max(1, (int) round($this->monitor->transition_time_milliseconds / 1000));
        $this->autoGeneration = (bool) $this->monitor->auto_generation;
        $this->autoGenerationWindowMinutes = (int) ($this->monitor->auto_generation_window_minutes ?: Monitor::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES);
        $this->backgroundSource = $this->monitor->resolvedBackgroundSource();
        $this->backgroundOverlayEnabled = (bool) $this->monitor->background_overlay_enabled;
        $this->headerTextIsLight = (bool) $this->monitor->header_text_is_light;
        $this->contentCardStyle = $this->monitor->content_card_style ?: Monitor::DEFAULT_CONTENT_CARD_STYLE;
        $this->existingImagePath = $this->monitor->image_path;
        $this->image = null;
        $this->removeExistingImage = false;
        $this->syncBaseline();
    }

    protected function syncBaseline(): void
    {
        $this->baseline = $this->snapshot();
        $this->isDirty = false;
    }

    protected function refreshDirtyState(): void
    {
        $this->isDirty = $this->snapshot() !== $this->baseline;
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshot(): array
    {
        return [
            'transitionTimeSeconds' => $this->transitionTimeSeconds,
            'autoGeneration' => $this->autoGeneration,
            'autoGenerationWindowMinutes' => $this->autoGenerationWindowMinutes,
            'backgroundSource' => $this->backgroundSource,
            'backgroundOverlayEnabled' => $this->backgroundOverlayEnabled,
            'headerTextIsLight' => $this->headerTextIsLight,
            'contentCardStyle' => $this->contentCardStyle,
            'hasPendingUpload' => $this->image !== null,
            'removeExistingImage' => $this->removeExistingImage,
        ];
    }
};
?>

@php
    $backgroundPresets = Monitor::backgroundPresets();
    $contentCardStyles = Monitor::contentCardStyles();
    $hasStoredUpload = filled($existingImagePath) && Storage::disk('public')->exists($existingImagePath);
    $resolvedSource = $backgroundSource === 'upload' && ($image !== null || $hasStoredUpload)
        ? 'upload'
        : ($backgroundSource !== 'upload' ? $backgroundSource : Monitor::DEFAULT_BACKGROUND_SOURCE);

    $uploadPreview = null;

    if ($image !== null) {
        $uploadPreview = $image->temporaryUrl();
    } elseif ($hasStoredUpload) {
        $uploadPreview = asset('storage/'.$existingImagePath);
    }

    if ($resolvedSource === 'upload') {
        $previewBackground = $uploadPreview;
    } else {
        $previewBackground = asset($backgroundPresets[$resolvedSource]['path'] ?? $backgroundPresets[Monitor::DEFAULT_BACKGROUND_SOURCE]['path']);
    }
@endphp

<div
    x-data="{ saved: false }"
    x-on:monitor-settings-saved.window="saved = true; setTimeout(() => saved = false, 2200)"
    class="space-y-5"
>
    <div x-show="saved" x-transition.opacity class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ __('Monitor Einstellungen gespeichert') }}
    </div>

    @if ($isDirty)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <div class="font-semibold">{{ __('Ungespeicherte Änderungen') }}</div>
            <p class="mt-1 text-amber-700">{{ __('Ihre Anpassungen sind erst nach dem Speichern auf Vorschau und Live-Anzeige sichtbar.') }}</p>
        </div>
    @endif

    <div class="space-y-3.5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
            <label class="text-sm font-medium leading-tight text-base-content md:w-2/3 md:text-base">{{ __('Seiten automatisch generieren') }}</label>
            <div class="flex md:w-1/3 md:max-w-[15rem] md:justify-end">
                <input type="checkbox" wire:model.live="autoGeneration" class="toggle toggle-primary shrink-0">
            </div>
        </div>
        @error('autoGeneration')
            <span class="block text-sm font-medium text-red-500">{{ $message }}</span>
        @enderror

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
            <label for="auto-window-minutes" class="text-sm font-medium leading-tight text-base-content md:w-2/3 md:text-base">{{ __('Besuche im Zeitraum (+/- Minuten)') }}</label>
            <div class="md:w-1/3 md:max-w-[15rem]">
                <input
                    id="auto-window-minutes"
                    type="number"
                    min="1"
                    max="180"
                    wire:model.live="autoGenerationWindowMinutes"
                    class="input input-bordered h-10 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 transition-all"
                >
                @error('autoGenerationWindowMinutes')
                    <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
            <label for="transition-time-seconds" class="text-sm font-medium leading-tight text-base-content md:w-2/3 md:text-base">{{ __('Seitenwechsel nach (Sekunden)') }}</label>
            <div class="md:w-1/3 md:max-w-[15rem]">
                <input
                    id="transition-time-seconds"
                    type="number"
                    min="1"
                    max="50"
                    wire:model.live="transitionTimeSeconds"
                    class="input input-bordered h-10 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 transition-all"
                >
                @error('transitionTimeSeconds')
                    <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-6">
            <label for="content-card-style" class="text-sm font-medium leading-tight text-base-content md:w-2/3 md:text-base">{{ __('Kartenstil für Datum und Besucher') }}</label>
            <div class="md:w-1/3 md:max-w-[15rem]">
                <select
                    id="content-card-style"
                    wire:model.live="contentCardStyle"
                    class="select select-bordered h-10 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 transition-all"
                >
                    @foreach ($contentCardStyles as $styleValue => $styleLabel)
                        <option value="{{ $styleValue }}">{{ $styleLabel }}</option>
                    @endforeach
                </select>
                @error('contentCardStyle')
                    <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Standard-Hintergrund') }}</h3>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($backgroundPresets as $source => $preset)
                @php
                    $cardPreview = $source === 'upload'
                        ? $uploadPreview
                        : asset($preset['path']);
                    $isSelected = $backgroundSource === $source;
                @endphp
                <label @class([
                    'cursor-pointer overflow-hidden rounded-[1.5rem] border bg-white transition-all',
                    'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' => $isSelected,
                    'border-slate-200 hover:border-slate-300' => ! $isSelected,
                ])>
                    <input type="radio" wire:model.live="backgroundSource" value="{{ $source }}" class="sr-only">
                    <div class="aspect-video w-full overflow-hidden bg-slate-100">
                        @if ($source === 'upload' && blank($uploadPreview))
                            <div class="flex h-full items-center justify-center px-4 text-center text-sm text-slate-500">
                                {{ __('Eigenes Bild hochladen') }}
                            </div>
                        @else
                            <img src="{{ $cardPreview }}" alt="{{ $preset['label'] }}" class="h-full w-full object-cover">
                        @endif
                    </div>
                    <div class="px-3 pt-2 pb-2.5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-slate-900">{{ $preset['label'] }}</span>
                            @if ($isSelected)
                                <span class="shrink-0 rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">{{ __('Aktiv') }}</span>
                            @endif
                        </div>
                    </div>
                </label>
            @endforeach
        </div>

        @if ($backgroundSource === 'upload')
            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">{{ __('Eigenes Hintergrundbild') }}</h4>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Empfohlen: 1920x1080 oder größer im Format 16:9.') }}</p>
                    </div>
                    @if ($image !== null || $hasStoredUpload)
                        <button type="button" wire:click="removeUploadedImage" class="btn btn-outline btn-sm rounded-xl">{{ __('Bild entfernen') }}</button>
                    @endif
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <input id="monitor-background-upload" type="file" wire:model="image" accept="image/png,image/jpeg,image/webp" class="file-input file-input-bordered w-full rounded-xl border-slate-300 bg-white">
                        <div wire:loading wire:target="image" class="mt-2 text-sm text-slate-500">{{ __('Bild wird geladen...') }}</div>
                        @error('image')
                            <span class="mt-2 block text-sm font-medium text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white">
                        <div class="aspect-video w-full overflow-hidden bg-slate-100">
                            @if (filled($previewBackground))
                                <img src="{{ $previewBackground }}" alt="{{ __('Monitor Hintergrund Vorschau') }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center px-6 text-center text-sm text-slate-500">
                                    {{ __('Die Vorschau erscheint nach dem Hochladen eines eigenen Bildes.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="flex min-h-12 items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
        <div>
            <div class="text-sm font-medium text-slate-900">{{ __('Bildfilter') }}</div>
            <p class="mt-1 text-sm text-slate-500">{{ __('Optionaler Aufheller für Texte auf kontrastreichen Bildern. Deaktiviert zeigt das Bild unverändert an.') }}</p>
        </div>
        <input type="checkbox" wire:model.live="backgroundOverlayEnabled" class="toggle toggle-primary shrink-0">
    </div>

    <div class="flex min-h-12 items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
        <div>
            <div class="text-sm font-medium text-slate-900">{{ __('Heller Headertext') }}</div>
            <p class="mt-1 text-sm text-slate-500">{{ __('Weiße Überschrift und Unterüberschrift für dunkle Hintergründe.') }}</p>
        </div>
        <input type="checkbox" wire:model.live="headerTextIsLight" class="toggle toggle-primary shrink-0">
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <button type="button" wire:click="resetToDefaults" class="btn btn-outline rounded-xl">{{ __('Standardwerte') }}</button>

        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save,image"
            @disabled(! $isDirty)
            class="btn btn-primary rounded-xl"
        >
            <span wire:loading.remove wire:target="save">{{ __('Speichern') }}</span>
            <span wire:loading wire:target="save">{{ __('Speichern...') }}</span>
        </button>
    </div>
</div>
