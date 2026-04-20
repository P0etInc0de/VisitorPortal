<?php

use App\Models\Monitor;
use Livewire\Component;

new class extends Component {
    public Monitor $monitor;

    public function with(): array
    {
        $this->monitor->refresh();
        $slides = $this->monitor->monitorSlides()->get();
        // Livewire re-runs this during the poll
        return [
            'monitor_slides' => $slides,
            'slides_hash' => md5($slides->max('updated_at')),
        ];
    }
};
?>
<div wire:poll.5s>
    <div class="bp-visit-item"
         id="index-slides-container"
         wire:key="container-{{ $slides_hash }}"
    >
        @forelse ($monitor_slides as $monitor_slide)

            {{-- Slide Number --}}
            <div class="bp-visit-timebox">
                <span class="bp-visit-time">{{ $monitor_slide->slide_number }}</span>
            </div>

            {{-- Main Info --}}
            <div class="bp-visit-main">
                <div class="bp-visit-title">{{ $monitor_slide->heading }}</div>
                <div class="bp-visit-company">{{ $monitor_slide->subheading }}</div>
                <div class="bp-visit-meta">
                    <div class="flex flex-wrap gap-2">
                        @foreach($monitor_slide->visitors as $visitor)
                            <button
                                type="button"
                                class="btn btn-xs btn-outline rounded-full"
                            >
                                {{ $visitor['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>


            <div class="form-group flex gap-2">
                {{--- delete and edit button ---}}
                @if($monitor->auto_generation === 0)
                    <a href="{{ route('monitors.slides.edit', [$monitor->id, $monitor_slide->id]) }}"
                       class="btn-sm rounded-xl btn">
                        {{ __('Bearbeiten') }}
                    </a>
                    <form method="POST"
                          action="{{ route('monitors.slides.destroy', [$monitor->id, $monitor_slide->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sm rounded-xl btn"
                                onclick="return confirm('{{ __('Sicher löschen?') }}')">
                            {{ __('Löschen') }}
                        </button>
                    </form>
                @endif
            </div>

        @empty
            <div class="py-12 text-center">
                <p class="text-slate-400">{{ __('Kein Seiten gefunden.') }}</p>
            </div>
        @endforelse
    </div>
</div>
