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
                <button type="button" class="btn btn-primary rounded-xl">
                    {{ __('Änderungen speichern') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-slate-900">{{ __('Layout') }}</h2>
                    <button type="button" class="btn btn-ghost btn-sm rounded-xl">
                        {{ __('Zurücksetzen') }}
                    </button>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ($layoutOptions as $option)
                        <label class="group cursor-pointer">
                            <input type="radio" name="layout_variant" class="peer sr-only" @checked($loop->first)>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 transition peer-checked:border-primary peer-checked:bg-primary/5 group-hover:border-slate-300">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-base font-medium text-slate-900">{{ __($option['name']) }}</div>
                                        <div class="mt-1 text-sm leading-6 text-slate-500">{{ __($option['description']) }}</div>
                                    </div>
                                    <div class="mt-1 h-4 w-4 rounded-full border border-slate-300 peer-checked:border-primary peer-checked:bg-primary"></div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                    @if ($option['id'] === 'classic')
                                        <div class="space-y-2">
                                            <div class="h-3 w-24 rounded bg-slate-300"></div>
                                            <div class="h-8 w-40 rounded bg-slate-900/80"></div>
                                            <div class="space-y-1 pt-2">
                                                <div class="h-2.5 w-full rounded bg-slate-200"></div>
                                                <div class="h-2.5 w-5/6 rounded bg-slate-200"></div>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 pt-2">
                                                <div class="h-12 rounded-xl bg-white border border-slate-200"></div>
                                                <div class="h-12 rounded-xl bg-white border border-slate-200"></div>
                                            </div>
                                        </div>
                                    @elseif ($option['id'] === 'hero')
                                        <div class="space-y-3">
                                            <div class="h-3 w-20 rounded bg-slate-300"></div>
                                            <div class="h-10 w-48 rounded bg-slate-900/80"></div>
                                            <div class="h-16 rounded-2xl bg-white border border-slate-200"></div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div class="h-10 rounded-xl bg-white border border-slate-200"></div>
                                                <div class="h-10 rounded-xl bg-white border border-slate-200"></div>
                                                <div class="h-10 rounded-xl bg-white border border-slate-200"></div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <div class="h-3 w-16 rounded bg-slate-300"></div>
                                                <div class="h-3 w-10 rounded bg-slate-200"></div>
                                            </div>
                                            <div class="grid gap-2 pt-1">
                                                <div class="h-9 rounded-xl bg-white border border-slate-200"></div>
                                                <div class="h-9 rounded-xl bg-white border border-slate-200"></div>
                                                <div class="h-9 rounded-xl bg-white border border-slate-200"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Hintergrund') }}</h2>

                <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($backgroundOptions as $option)
                                <label class="group cursor-pointer">
                                    <input type="radio" name="background_variant" class="peer sr-only" @checked($loop->first)>

                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/15 group-hover:border-slate-300">
                                        <div class="h-28 bg-gradient-to-br {{ $option['class'] }}"></div>
                                        <div class="flex items-center justify-between gap-3 p-4">
                                            <span class="text-sm font-medium text-slate-800">{{ __($option['name']) }}</span>
                                            <span class="h-4 w-4 rounded-full border border-slate-300 peer-checked:border-primary peer-checked:bg-primary"></span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
                        <div class="flex h-full flex-col justify-between gap-4">
                            <div>
                                <h3 class="text-base font-medium text-slate-900">{{ __('Eigenes Bild') }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ __('PNG oder JPG für den Monitor-Hintergrund auswählen.') }}
                                </p>
                            </div>

                            <label class="flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center transition hover:border-slate-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16.5v1.25A2.25 2.25 0 006.25 20h11.5A2.25 2.25 0 0020 17.75V16.5" />
                                </svg>
                                <span class="text-sm font-medium text-slate-800">{{ __('Bild hochladen') }}</span>
                                <span class="mt-1 text-xs text-slate-500">{{ __('Datei auswählen') }}</span>
                                <input type="file" class="hidden" accept=".png,.jpg,.jpeg,.webp">
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Inhalt') }}</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('Überschrift') }}</label>
                        <input
                            type="text"
                            class="input input-bordered h-12 w-full rounded-xl border-slate-300 bg-white"
                            value="{{ __('Herzlich willkommen') }}"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('Unterzeile') }}</label>
                        <textarea class="textarea textarea-bordered min-h-28 w-full rounded-xl border-slate-300 bg-white">{{ __('Wir freuen uns auf Ihren Besuch.') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Anzeigeoptionen') }}</h2>

                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                        <div>
                            <div class="font-medium text-slate-900">{{ __('Logo anzeigen') }}</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                        <div>
                            <div class="font-medium text-slate-900">{{ __('Datum anzeigen') }}</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="checkbox" class="checkbox checkbox-sm mt-1" checked>
                        <div>
                            <div class="font-medium text-slate-900">{{ __('Heutige Besucher anzeigen') }}</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="checkbox" class="checkbox checkbox-sm mt-1">
                        <div>
                            <div class="font-medium text-slate-900">{{ __('Logo auf Weiß freistellen') }}</div>
                        </div>
                    </label>
                </div>
            </section>
        </div>

        <aside class="xl:sticky xl:top-6 xl:self-start">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Vorschau') }}</h2>
                </div>

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
                                @foreach ($visits as $visit)
                                    <div class="rounded-3xl bg-white/82 p-5 shadow-sm ring-1 ring-slate-200/80 backdrop-blur">
                                        <div class="text-sm font-medium text-slate-500">{{ $visit['time'] }}</div>
                                        <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                                            {{ $visit['name'] }}
                                        </div>
                                        <div class="mt-1 text-base text-slate-600">{{ $visit['company'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-auto pt-6">
                                <div class="text-sm text-slate-500">
                                    {{ __('Empfang') }} · {{ __('Self-Check-in') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</x-app-layout>
