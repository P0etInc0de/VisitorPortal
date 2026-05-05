<?php

use App\Models\Monitor;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public Monitor $monitor;

    #[On('monitor-updated')]
    public function refreshSlides(): void {}

    public function toggleSlideVisibility(int $slideId): void
    {
        $slide = $this->monitor->monitorSlides()->findOrFail($slideId);

        abort_unless(auth()->user()?->can('update', $this->monitor) && auth()->user()?->can('update', $slide), 403);

        $slide->update([
            'is_active' => ! $slide->is_active,
        ]);

        $this->dispatch('monitor-updated');
    }

    public function deleteSlide(int $slideId): void
    {
        $slide = $this->monitor->monitorSlides()->findOrFail($slideId);

        abort_unless(auth()->user()?->can('update', $this->monitor) && auth()->user()?->can('delete', $slide), 403);

        if (filled($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
        }

        $slide->delete();

        $this->dispatch('monitor-updated');
    }

    public function with(): array
    {
        $this->monitor->refresh();
        $slides = $this->monitor->monitorSlides()
            ->orderBy('is_auto_generated')
            ->orderBy('slide_number')
            ->get();

        return [
            'monitor_slides' => $slides,
            'slides_hash' => md5((string) $slides->max('updated_at').$this->monitor->updated_at),
            'empty_message' => $this->monitor->auto_generation
                ? __('Keine geplanten Besuche im Zeitraum von +/- :minutes Minuten.', ['minutes' => $this->monitor->auto_generation_window_minutes])
                : __('Noch keine Seiten angelegt.'),
        ];
    }
};
?>
<div wire:poll.5s>
    <div class="space-y-4" id="index-slides-container" wire:key="container-{{ $slides_hash }}">
        <div class="flex flex-col gap-3 rounded-2xl border border-base-300 bg-base-200 p-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="font-medium text-base-content">
                    {{ $monitor->auto_generation ? __('Automatische Erstellung ist aktiv') : __('Manuelle Seitenbearbeitung ist aktiv') }}
                </div>
            </div>

            @if($monitor->auto_generation === false)
                <a type="button" href="{{ route('monitors.slides.create', $monitor) }}" class="btn btn-primary btn-sm rounded-xl">{{ __('Neue Seite') }}</a>
            @endif
        </div>

        <div class="rounded-3xl border border-primary/20 bg-primary/8 px-5 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-primary">{{ __('Allgemeine Begrüßungsseite') }}</div>
                    <div class="mt-3 text-2xl font-semibold leading-tight tracking-tight text-base-content">{{ $monitor->fallback_heading }}</div>
                    @if (filled($monitor->fallback_subheading))
                        <p class="mt-2 text-sm text-base-content/70">{{ $monitor->fallback_subheading }}</p>
                    @endif

                    <div class="mt-4">
                        <span class="inline-flex items-center rounded-full bg-base-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ __('Immer verfügbar') }}</span>
                    </div>
                </div>

                <a href="{{ route('monitors.fallback.edit', $monitor) }}" class="btn btn-sm rounded-xl">{{ __('Bearbeiten') }}</a>
            </div>
        </div>

        @forelse ($monitor_slides as $monitor_slide)
            <article class="mb-2 rounded-3xl border border-base-300 bg-base-100 px-5 py-5">
                <div class="grid gap-x-6 gap-y-3 lg:grid-cols-[auto_minmax(0,1fr)_auto_auto] lg:items-start">
                    <div class="pt-1 lg:row-span-2">
                        <div class="text-[1.22rem] font-bold leading-none tracking-tight text-base-content">
                            {{ $monitor_slide->slide_number }}
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="text-base font-bold leading-tight tracking-tight text-base-content">
                            {{ $monitor_slide->heading }}
                        </div>
                        @if($monitor_slide->subheading)
                            <p class="mt-1 text-sm text-base-content/65">
                                {{ $monitor_slide->subheading }}
                            </p>
                        @endif
                    </div>

                    @php
                        $isEffectivelyActive = $monitor->auto_generation
                            ? $monitor_slide->is_auto_generated
                            : (! $monitor_slide->is_auto_generated && $monitor_slide->is_active);
                    @endphp

                    <span class="badge rounded-full px-3 py-3 text-sm font-semibold lg:self-start {{ $isEffectivelyActive ? 'badge-success' : 'badge-ghost' }}">
                        {{ $isEffectivelyActive ? __('Aktiv') : __('Inaktiv') }}
                    </span>

                    @if($monitor->auto_generation === false)
                        <div class="flex flex-col items-end gap-2 lg:row-span-2">
                            <a href="{{ route('monitors.slides.edit', [$monitor->id, $monitor_slide->id]) }}" class="btn-sm rounded-xl btn min-w-[6.5rem] px-3 justify-center">
                                {{ __('Bearbeiten') }}
                            </a>
                            <button type="button" class="btn-sm rounded-xl btn min-w-[6.5rem] px-3 justify-center" wire:click="toggleSlideVisibility({{ $monitor_slide->id }})">
                                {{ $monitor_slide->is_active ? __('Ausblenden') : __('Anzeigen') }}
                            </button>
                            <button type="button" class="btn-sm rounded-xl btn min-w-[6.5rem] px-3 justify-center" x-on:click="if (confirm('{{ __('Sicher löschen?') }}')) { $wire.deleteSlide({{ $monitor_slide->id }}) }">
                                {{ __('Löschen') }}
                            </button>
                        </div>
                    @endif

                    @if(! empty($monitor_slide->visitors))
                        <div class="lg:col-start-2 lg:col-end-4">
                            <div class="grid max-w-full grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-4">
                                @foreach($monitor_slide->visitors as $visitor)
                                    <span class="inline-flex w-fit max-w-full items-center justify-self-start rounded-full border border-base-300 bg-base-200/60 px-2.5 py-1 text-xs leading-tight text-base-content/75">
                                        {{ $visitor['name'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-base-300 bg-base-200 px-6 py-12 text-center">
                <p class="text-base font-medium text-base-content/80">{{ $empty_message }}</p>
                @if ($monitor->auto_generation)
                    <p class="mt-2 text-sm text-base-content/65">{{ __('Die Live-Anzeige zeigt in der Zwischenzeit automatisch die allgemeine Begrüßungsseite an.') }}</p>
                    <p class="mt-1 text-sm text-base-content/65">{{ __('Entwürfe werden bei der automatischen Erstellung nicht berücksichtigt.') }}</p>
                @endif
            </div>
        @endforelse
    </div>
</div>
