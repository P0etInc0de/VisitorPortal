<section class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] xl:items-start">
    <div class="grid min-w-0 content-start gap-3 xl:self-start">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-base-300 bg-base-200 p-3.5">
            <label class="form-control w-full">
                <div class="label px-0 pb-2">
                    <span class="label-text text-sm font-medium text-base-content">{{ __('Manuell Name hinzufügen') }}</span>
                </div>
            </label>

            <div class="relative min-w-0">
                <input
                    id="manualNameInput"
                    type="text"
                    maxlength="50"
                    placeholder="{{ __('Name') }}"
                    onkeydown="if (event.key === 'Enter') { event.preventDefault(); addManualVisitor(); }"
                    class="input input-bordered h-11 w-full min-w-0 rounded-xl border-base-300 bg-base-100 pr-14 focus:border-primary transition-all"
                >

                <button
                    type="button"
                    class="btn btn-primary btn-sm btn-circle absolute top-1/2 right-1.5 inline-flex h-9 min-h-0 w-9 -translate-y-1/2 items-center justify-center rounded-full p-0"
                    onclick="addManualVisitor()"
                    aria-label="{{ __('Zur Liste hinzufügen') }}"
                    title="{{ __('Zur Liste hinzufügen') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="min-w-0 rounded-2xl border border-base-300 bg-base-200 p-3.5">
            <label class="form-control w-full">
                <div class="label px-0 pb-2">
                    <span class="label-text text-sm font-medium text-base-content">{{ __('Ausgewählte Besucher') }}</span>
                </div>
            </label>

            <div id="selectedVisitors" class="flex max-h-64 min-h-14 flex-wrap content-start gap-2 overflow-y-auto rounded-xl border border-base-300 bg-base-100 p-2.5">
                <!-- selected visitors are put here -->
            </div>

            <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <span id="visitorCount" class="text-sm font-medium text-base-content/75">
                    {{ __(':count von :max Besuchern ausgewählt', ['count' => 0, 'max' => 6]) }}
                </span>
                <span id="visitorLimitHint" class="text-xs font-medium text-base-content/60">
                    {{ __('Maximal 6 Besucher je Seite') }}
                </span>
            </div>

            <span
                class="mt-2 @error('visitors') block @else hidden @enderror rounded-xl border border-warning/30 bg-warning/10 px-3 py-2 text-sm font-medium text-warning"
                id="visitorError"
                role="status"
                aria-live="polite"
                data-duplicate-message="{{ __('Besucher ist bereits hinzugefügt.') }}"
                data-limit-message="{{ __('Maximal 6 Besucher je Seite erreicht.') }}"
            >@error('visitors'){{ $message }}@enderror</span>

            <input type="hidden" id="visitorsInput" name="visitors">
        </div>
    </div>

    <div class="grid min-w-0 gap-3">
        <div class="min-w-0 rounded-2xl border border-base-300 bg-base-200 p-3.5">
            <h3 class="text-sm font-medium text-base-content">{{ __('Heutige Besuche') }}</h3>

            <div class="mt-3 grid gap-2">
                @forelse ($todayVisits as $visit)
                    @php
                        $visitStatusLabel = match ((string) $visit->status) {
                            'canceled' => __('Abgesagt'),
                            'completed' => __('Abgeschlossen'),
                            'draft' => __('Entwurf'),
                            default => __('Geplant'),
                        };

                        $visitStatusClass = match ((string) $visit->status) {
                            'canceled' => 'badge-neutral badge-outline',
                            'completed' => 'badge-success badge-outline',
                            'draft' => 'badge-error badge-outline',
                            default => 'badge-warning badge-outline',
                        };
                    @endphp

                    <article class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 shrink-0 text-[1.05rem] font-semibold tracking-tight text-base-content">
                                        {{ \Carbon\Carbon::parse($visit->scheduled_from)->format('H:i') }}
                                    </div>

                                    <div class="min-w-0">
                                        <div class="truncate text-base font-semibold tracking-tight text-base-content">{{ $visit->title }}</div>
                                    </div>
                                </div>

                                @if ($visit->visitors->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2 pl-[4.5rem]">
                                        @foreach ($visit->visitors as $visitor)
                                            @php
                                                $visitorDisplayName = trim($visitor->title.' '.$visitor->first_name.' '.$visitor->name);
                                                $visitorPayload = [
                                                    'key' => "visit-{$visitor->id}",
                                                    'id' => $visitor->id,
                                                    'name' => $visitorDisplayName,
                                                    'source' => [
                                                        'title' => $visitor->title,
                                                        'first_name' => $visitor->first_name,
                                                        'name' => $visitor->name,
                                                        'company' => $visitor->company,
                                                    ],
                                                ];
                                            @endphp
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-outline rounded-full"
                                                onclick="addVisitor(@js($visitorPayload))"
                                            >
                                                + {{ $visitorDisplayName }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <span class="badge {{ $visitStatusClass }} shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold">
                                {{ $visitStatusLabel }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 px-4 py-5 text-sm text-base-content/65">
                        {{ __('Für heute sind aktuell keine Termine geplant.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a type="button" class="btn btn-sm rounded-xl" href="{{ $cancelUrl }}">{{ __('Abbrechen') }}</a>
            <input type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{ $submitLabel }}">
        </div>
    </div>
</section>
