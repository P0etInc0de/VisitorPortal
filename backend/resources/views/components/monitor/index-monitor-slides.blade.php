@props(['monitor_slides'])
<x-bp.card title={{__('Monitor Seiten')}}>
    <x-slot:actions>
        <button href="" class="btn btn-primary btn-sm rounded-xl">Neuer Monitor</button>
    </x-slot:actions>
        @forelse ($monitor_slides as $monitor_slide)
            <div class="bp-visit-item">

                {{-- ID / Time Box --}}
                <div class="bp-visit-timebox">
                    <span class="bp-visit-time">#{{ $monitor_slide->slide_numbe }}</span>
                </div>

                {{-- Main Info --}}
                <div class="bp-visit-main">
                    <div class="bp-visit-title">{{ $monitor->name }}</div>
                    <div class="bp-visit-meta">
                        {{ __('Erstellt am') }} {{ $monitor->created_at->format('d.m.Y H:i') }}
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bp-inline-actions">
                    <a href="{{ route('monitors.edit', $monitor) }}" class="btn btn-outline btn-sm">
                        {{ __('Edit') }}
                    </a>
                </div>
            </div>
        @empty
            <div class="py-12 text-center">
                <p class="text-slate-400">{{ __('Kein Monitor gefunden.') }}</p>
            </div>
        @endforelse
</x-bp.card>
