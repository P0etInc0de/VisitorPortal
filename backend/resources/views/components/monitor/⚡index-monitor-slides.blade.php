<?php

use App\Models\Monitor;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public Monitor $monitor;

    #[On('monitor-updated')]
    public function refreshSlides(): void {}

    public function with(): array
    {
        $this->monitor->refresh();
        $slides = $this->monitor->monitorSlides()->orderBy('slide_number')->get();

        return [
            'monitor_slides' => $slides,
            'slides_hash' => md5((string) $slides->max('updated_at').$this->monitor->updated_at),
            'empty_message' => $this->monitor->auto_generation
                ? __('Keine registrierten Besuche im Zeitraum von +/- :minutes Minuten.', ['minutes' => $this->monitor->auto_generation_window_minutes])
                : __('Noch keine Seiten angelegt.'),
        ];
    }
};
?>
<div wire:poll.5s>
    <div class="space-y-4" id="index-slides-container" wire:key="container-{{ $slides_hash }}">
        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="font-medium text-slate-900">
                    {{ $monitor->auto_generation ? __('Automatische Erstellung ist aktiv') : __('Manuelle Seitenbearbeitung ist aktiv') }}
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $monitor->auto_generation
                        ? __('Neue Seiten werden automatisch für passende Besuche erzeugt und alle 5 Minuten aktualisiert.')
                        : __('Sie können eigene Seiten anlegen, bearbeiten und in der Reihenfolge anpassen.') }}
                </p>
            </div>

            @if($monitor->auto_generation === false)
                <a type="button" href="{{ route('monitors.slides.create', $monitor) }}" class="btn btn-primary btn-sm rounded-xl">{{ __('Neue Seite') }}</a>
            @endif
        </div>

        @forelse ($monitor_slides as $monitor_slide)
            <x-bp.list-item :title="$monitor_slide->heading" :subtitle="$monitor_slide->subheading" :index="$monitor_slide->slide_number">
                <x-slot:items>
                    @foreach($monitor_slide->visitors as $visitor)
                        <span class="inline-flex items-center rounded-full border border-base-300 bg-base-200/60 px-2.5 py-1 text-xs leading-tight text-base-content/75">
                            {{ $visitor['name'] }}
                        </span>
                    @endforeach
                </x-slot:items>
                <x-slot:action>
                    <div class="flex gap-2">
                        @if($monitor->auto_generation === false)
                            <a href="{{ route('monitors.slides.edit', [$monitor->id, $monitor_slide->id]) }}" class="btn-sm rounded-xl btn">
                                {{ __('Bearbeiten') }}
                            </a>
                            <form method="POST" action="{{ route('monitors.slides.destroy', [$monitor->id, $monitor_slide->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-sm rounded-xl btn" onclick="return confirm('{{ __('Sicher löschen?') }}')">
                                    {{ __('Löschen') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </x-slot:action>
            </x-bp.list-item>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                <p class="text-base font-medium text-slate-700">{{ $empty_message }}</p>
                @if ($monitor->auto_generation)
                    <p class="mt-2 text-sm text-slate-500">{{ __('Die Live-Anzeige zeigt in der Zwischenzeit automatisch die allgemeine Begrüßungsseite an.') }}</p>
                @endif
            </div>
        @endforelse
    </div>
</div>
