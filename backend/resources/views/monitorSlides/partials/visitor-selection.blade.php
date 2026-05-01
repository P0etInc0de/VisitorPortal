<section class="grid gap-3 xl:grid-cols-3 xl:items-start">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 xl:col-span-1">
        <label class="form-control w-full">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">{{ __('Manuell Name hinzufügen') }}</span>
            </div>
        </label>

        <div class="relative">
            <input
                id="manualNameInput"
                type="text"
                maxlength="50"
                placeholder="{{ __('Name') }}"
                onkeydown="if (event.key === 'Enter') { event.preventDefault(); addManualVisitor(); }"
                class="input input-bordered h-11 w-full rounded-xl border-slate-300 bg-white pr-14 focus:border-indigo-500 transition-all"
            >

            <button
                type="button"
                class="btn btn-primary btn-sm btn-circle absolute top-1/2 right-1.5 h-9 min-h-0 w-9 -translate-y-1/2 rounded-full text-xl leading-none"
                onclick="addManualVisitor()"
                aria-label="{{ __('Zur Liste hinzufügen') }}"
                title="{{ __('Zur Liste hinzufügen') }}"
            >
                +
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5 xl:col-span-2">
        <h3 class="text-sm font-semibold text-slate-900">{{ __('Heutige Besuche') }}</h3>

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

                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
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
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-outline rounded-full"
                                            onclick='addVisitor(@json(["key" => "visit-{$visitor->id}", "id" => $visitor->id, "name" => trim($visitor->title." ".$visitor->first_name." ".$visitor->name)]))'
                                        >
                                            + {{ trim($visitor->title.' '.$visitor->first_name.' '.$visitor->name) }}
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
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-5 text-sm text-slate-500">
                    {{ __('Für heute sind aktuell keine Termine geplant.') }}
                </div>
            @endforelse
        </div>
    </div>

    <div class="xl:col-span-3">
        <label class="form-control w-full">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">{{ __('Ausgewählte Besucher') }}</span>
            </div>
        </label>

        <div id="selectedVisitors" class="flex min-h-14 flex-wrap gap-2 rounded-xl border border-slate-200 bg-base-100 p-2.5">
            <!-- selected visitors are put here -->
        </div>
        <span class="mt-1 block text-sm font-medium text-red-500" id="visitorError"></span>

        <input type="hidden" id="visitorsInput" name="visitors">
    </div>
</section>
