<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Editor') }}
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('monitor.live') }}" class="btn btn-outline rounded-xl">
                    {{ __('Live-Anzeige öffnen') }}
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Das Haupt-Grid: Links Formular, Rechts Sticky-Preview --}}
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">

        {{-- LINKER BEREICH: FORMULAR --}}
        <div class="space-y-6">
            <div class="bp-page">
                <div class="bp-content">

                    <x-alert/>

                    <x-bp.card title="{{ __('Monitor Seiten') }}">
                        <x-slot:actions>
                            <a type="button" href="{{route('monitor_slides.create')}}" class="btn btn-primary btn-sm rounded-xl">{{__('Neue Seite')}}</a>
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
                            </div>
                        @endforelse
                    </x-bp.card>
                </div>
            </div>
        </div>
            {{-- RECHTER BEREICH: LIVE PREVIEW (STICKY) --}}
        <aside class="xl:sticky xl:top-6 xl:self-start">
            <x-bp.card title="{{ __('Vorschau') }}">

                <div class="p-5">
                    <div class="aspect-[16/10] overflow-hidden rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200">
                        <div class="flex h-full flex-col p-8 lg:p-10">
                            <div class="flex items-start justify-between gap-6">
                                <div class="flex items-center gap-4">
                                    <img
                                        src="{{ asset('images/logo.png') }}"
                                        alt="Logo"
                                        class="h-10 w-auto object-contain"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                    <div class="hidden h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">
                                        ACD
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-white/85 px-4 py-2 text-right shadow-sm ring-1 ring-slate-200/80 backdrop-blur">
                                    <div class="text-xs uppercase tracking-[0.24em] text-slate-500">{{ __('Heute') }}</div>
                                    <div class="mt-1 text-lg font-semibold text-slate-900">30.03.2026</div>
                                </div>
                            </div>

                            <div class="mt-10 max-w-3xl">
                                <h3 class="text-4xl font-semibold tracking-tight text-slate-900 lg:text-5xl">
                                    {{ __('Herzlich willkommen') }}
                                </h3>
                                <p class="mt-4 text-lg leading-8 text-slate-700">
                                    {{ __('Wir freuen uns auf Ihren Besuch.') }}
                                </p>
                            </div>

                            <div class="mt-10 grid gap-4 md:grid-cols-2">
                            </div>

                            <div class="mt-auto pt-6">
                                <div class="text-sm text-slate-500">
                                    {{ __('Empfang') }} · {{ __('Self-Check-in') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-bp.card>
        </aside>

    </div>

</x-app-layout>
