<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">
                    {{ __('Monitor Übersicht') }}
                </h1>
            </div>

            <div class="bp-screen-actions">
                <a href="{{ route('monitors.create') }}" type="button" class="btn btn-primary rounded-xl">
                    {{ __('Neuer Monitor') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bp-page">
        <div class="bp-content">

            <x-alert/>

            <x-bp.card title={{__('Monitor Seiten')}}>
                <x-slot:actions>
                    <button href="" class="btn btn-primary btn-sm rounded-xl">Neuer Monitor</button>
                </x-slot:actions>
                @forelse ($monitor_slides as $monitor_slide)
                    <div class="bp-visit-item">

                        {{-- ID / Time Box --}}
                        <div class="bp-visit-timebox">
                            <span class="bp-visit-time">#{{ $monitor_slide->slide_number }}</span>
                        </div>

                        {{-- Main Info --}}
                        <div class="bp-visit-main">
                            <div class="bp-visit-title">{{ $monitor_slide->heading }}</div>
                            <div class="bp-visit-meta">
                                {{ __('Personen anzeige:') }} {{ $monitor_slide->viitors }}
                            </div>
                        </div>

                @empty
                    <div class="py-12 text-center">
                        <p class="text-slate-400">{{ __('Kein Seiten gefunden.') }}</p>
                    </div>
                @endforelse
            </x-bp.card>

        </div>
    </div>
</x-app-layout>
