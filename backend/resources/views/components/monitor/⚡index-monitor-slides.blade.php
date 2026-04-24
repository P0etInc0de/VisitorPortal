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
    <div class=""
         id="index-slides-container"
         wire:key="container-{{ $slides_hash }}"
    >
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
                </x-slot:action>
            </x-bp.list-item>

        @empty
            <div class="py-12 text-center">
                <p class="text-slate-400">{{ __('Kein Seiten gefunden.') }}</p>
            </div>
       @endforelse

    </div>
</div>
