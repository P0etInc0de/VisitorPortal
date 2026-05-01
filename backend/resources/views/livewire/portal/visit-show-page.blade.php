<div>
    <div class="mb-8">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ $visit->display_title }}</h1>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_22rem] xl:items-start">
        <div class="grid gap-6">
            @if (session('status'))
                <div class="alert alert-success rounded-2xl shadow-sm">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <section class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Status') }}</div>
                        <div class="mt-2">
                            <span class="badge {{ $visitStatus['class'] }} rounded-full text-sm font-semibold">{{ $visitStatus['label'] }}</span>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Zeitfenster') }}</div>
                        <div class="mt-2 text-base font-medium text-base-content">{{ $visitMeta['timeframe'] }}</div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Host') }}</div>
                        <div class="mt-2 text-base font-medium text-base-content">{{ $visitMeta['host'] }}</div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Vertretung') }}</div>
                        <div class="mt-2 text-base font-medium text-base-content">{{ $visitMeta['reception'] }}</div>
                    </div>
                </div>

                @if (!empty($visit->notes))
                    <div class="mt-6 border-t border-base-300 pt-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Notiz') }}</div>
                        <p class="mt-2 text-base leading-7 text-base-content/80">{{ $visit->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h2>
                </div>

                @if (count($participants) === 0)
                    <div class="rounded-2xl border border-dashed border-base-300 bg-base-100/70 px-4 py-8 text-sm text-base-content/65">
                        {{ __('Diesem Besuch sind noch keine Teilnehmenden zugeordnet.') }}
                    </div>
                @else
                    <div class="grid gap-4">
                        @foreach ($participants as $participant)
                            <article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-semibold tracking-tight text-base-content sm:text-xl">{{ $participant['display_name'] }}</h3>
                                            <span class="badge {{ $participant['status']['class'] }} rounded-full text-xs font-semibold">{{ $participant['status']['label'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1.45fr)_minmax(0,0.85fr)_minmax(0,0.85fr)]">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('E-Mail') }}</div>
                                        <div class="mt-1.5 break-words text-base text-base-content">{{ $participant['email'] ?: '–' }}</div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Telefon') }}</div>
                                        <div class="mt-1.5 text-base text-base-content">{{ $participant['phone'] ?: '–' }}</div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Unternehmen') }}</div>
                                        <div class="mt-1.5 text-base text-base-content">{{ $participant['company'] ?: '–' }}</div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="grid gap-6 xl:sticky xl:top-6">
            <section class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Termin verschieben') }}</h2>

                <form wire:submit.prevent="saveSchedule" class="grid gap-4">
                    <div>
                        <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Beginn') }}</span></label>
                        <input type="datetime-local" wire:model.defer="scheduledFrom" class="input input-bordered w-full @error('scheduledFrom') input-error @enderror">
                        @error('scheduledFrom')
                            <div class="mt-2 text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Ende') }}</span></label>
                        <input type="datetime-local" wire:model.defer="scheduledUntil" class="input input-bordered w-full @error('scheduledUntil') input-error @enderror">
                        @error('scheduledUntil')
                            <div class="mt-2 text-sm text-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full rounded-xl" wire:loading.attr="disabled" wire:target="saveSchedule">
                        {{ __('Termin verschieben') }}
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Aktionen') }}</h2>

                <div class="grid gap-3">
                    <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline w-full rounded-xl">
                        {{ __('Bearbeiten') }}
                    </a>

                    @if ($visit->status === 'canceled' || filled($visit->canceled_at))
                        <button type="button" class="btn btn-primary w-full rounded-xl" wire:click="reopenVisit" wire:loading.attr="disabled" wire:target="reopenVisit">
                            {{ __('Besuch wieder öffnen') }}
                        </button>
                    @else
                        <button type="button" class="btn btn-outline w-full rounded-xl" wire:click="cancelVisit" wire:loading.attr="disabled" wire:target="cancelVisit">
                            {{ __('Besuch absagen') }}
                        </button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
