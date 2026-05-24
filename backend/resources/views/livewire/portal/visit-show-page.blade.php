<div>
    @php
        $canUpdateVisit = auth()->user()?->can('update', $visit) ?? false;
        $canCancelVisit = auth()->user()?->can('cancel', $visit) ?? false;
        $visitIsCanceled = $visit->status === 'canceled' || filled($visit->canceled_at);
        $showEditAction = $canUpdateVisit;
        $showReopenAction = $canUpdateVisit && $visitIsCanceled;
        $showCancelAction = $canCancelVisit && ! $visitIsCanceled;
        $showCancelOnlyCard = $showCancelAction && ! $canUpdateVisit;
        $showActionCard = $showEditAction || $showReopenAction || ($showCancelAction && ! $showCancelOnlyCard);
        $showSidebar = $recurrenceMeta || $canUpdateVisit || $showActionCard || $showCancelOnlyCard;
    @endphp

    <div
        data-testid="visit-detail-grid"
        x-data="{
            rescheduleMinHeight: '',
            alignThreshold: 36,
            alignTimer: null,
            init() {
                const schedule = () => {
                    if (this.alignTimer) {
                        window.cancelAnimationFrame(this.alignTimer);
                    }

                    this.alignTimer = window.requestAnimationFrame(() => this.alignTopCards());
                };

                this.$nextTick(() => {
                    if (this.$refs.infoCard && window.ResizeObserver) {
                        this.alignmentObserver = new ResizeObserver(schedule);
                        this.alignmentObserver.observe(this.$refs.infoCard);
                    }

                    window.addEventListener('resize', schedule);
                    schedule();
                });
            },
            alignTopCards() {
                if (!window.matchMedia('(min-width: 1280px)').matches || !this.$refs.infoCard || !this.$refs.rescheduleCard) {
                    this.rescheduleMinHeight = '';
                    return;
                }

                this.rescheduleMinHeight = '';

                this.$nextTick(() => {
                    const infoHeight = this.$refs.infoCard.getBoundingClientRect().height;
                    const rescheduleHeight = this.$refs.rescheduleCard.getBoundingClientRect().height;
                    const difference = infoHeight - rescheduleHeight;

                    this.rescheduleMinHeight = difference > 0 && difference <= this.alignThreshold
                        ? `${Math.ceil(infoHeight)}px`
                        : '';
                });
            },
        }"
        @class([
            'grid gap-6 xl:items-start',
            'xl:grid-cols-3',
        ])
    >
        @if (session('status'))
            <div class="alert alert-success rounded-2xl shadow-sm xl:col-span-2">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div @class([
            'grid min-w-0 gap-6',
            'xl:col-span-2',
        ]) data-testid="visit-detail-content">
            <section x-ref="infoCard" data-testid="visit-info-card" class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
                <div @class([
                    'grid gap-5 md:grid-cols-2',
                    '2xl:grid-cols-[minmax(7rem,0.65fr)_minmax(15rem,1.45fr)_minmax(12rem,1fr)]' => $recurrenceMeta,
                    '2xl:grid-cols-[minmax(7rem,0.65fr)_minmax(15rem,1.45fr)_minmax(10rem,1fr)_minmax(10rem,1fr)]' => ! $recurrenceMeta,
                ])>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Status') }}</div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="badge {{ $visitStatus['class'] }} rounded-full text-sm font-semibold">{{ $visitStatus['label'] }}</span>
                            @if ($recurrenceMeta)
                                <x-recurrence-indicator :badge="true" :modified="$recurrenceMeta['is_modified']" :label="$recurrenceMeta['badge_label']" />
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Zeitfenster') }}</div>
                        <div class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-1 text-base font-medium text-base-content">
                            <span class="whitespace-nowrap">{{ $visitMeta['date'] }}</span>
                            @if ($visitMeta['timeRange'] !== '–')
                                <span class="text-base-content/45">|</span>
                                <span class="whitespace-nowrap">{{ $visitMeta['timeRange'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Host') }}</div>
                        <div class="mt-2 text-base font-medium text-base-content">{{ $visitMeta['host'] }}</div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Vertretung') }}</div>
                        <div class="mt-2 text-base font-medium text-base-content">{{ $visitMeta['substitute'] }}</div>
                    </div>

                    @if ($recurrenceMeta)
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Wiederholung') }}</div>
                            <div class="mt-2 text-base font-medium text-base-content">{{ $recurrenceMeta['rule'] }}</div>
                        </div>
                    @endif

                    @if ($recurrenceMeta && !empty($recurrenceMeta['progress']))
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Serie') }}</div>
                            <div class="mt-2 text-base font-medium text-base-content">{{ $recurrenceMeta['progress'] }}</div>
                        </div>
                    @endif

                    @if ($recurrenceMeta && $recurrenceMeta['is_modified'])
                        <div class="md:col-span-2 2xl:col-span-3 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm font-medium text-warning">
                            {{ $recurrenceMeta['modified_note'] }}
                        </div>
                    @endif
                </div>

                @if (!empty($visit->notes))
                    <div class="mt-6 border-t border-base-300 pt-6">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-base-content/50">{{ __('Notiz') }}</div>
                        <p class="mt-2 text-base leading-7 text-base-content/80">{{ $visit->notes }}</p>
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
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

        @if ($showSidebar)
            <div class="grid min-w-0 gap-6 xl:sticky xl:top-6">
                @if ($canUpdateVisit)
                    <section x-ref="rescheduleCard" data-testid="visit-reschedule-card" :style="rescheduleMinHeight ? { minHeight: rescheduleMinHeight } : {}" class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
                        <h2 class="mb-3 text-xl font-bold text-base-content">{{ __('Termin verschieben') }}</h2>

                        <form wire:submit.prevent="saveSchedule" class="grid gap-3">
                            <div>
                                <label class="label px-0 pb-1.5"><span class="label-text font-medium">{{ __('Beginn') }}</span></label>
                                <input type="datetime-local" wire:model.defer="scheduledFrom" class="input input-bordered w-full @error('scheduledFrom') input-error @enderror">
                                @error('scheduledFrom')
                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label class="label px-0 pb-1.5"><span class="label-text font-medium">{{ __('Ende') }}</span></label>
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
                @endif

                @if ($showActionCard)
                    <section data-testid="visit-actions-card" class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
                        <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Aktionen') }}</h2>

                        <div class="grid gap-3">
                            @if ($showEditAction)
                                <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline w-full rounded-xl">
                                    {{ __('Bearbeiten') }}
                                </a>
                            @endif

                            @if ($showReopenAction)
                                <button type="button" class="btn btn-primary w-full rounded-xl" wire:click="reopenVisit" wire:loading.attr="disabled" wire:target="reopenVisit">
                                    {{ __('Besuch wieder öffnen') }}
                                </button>
                            @endif

                            @if ($showCancelAction && ! $showCancelOnlyCard)
                                <button type="button" class="btn btn-outline w-full rounded-xl" wire:click="cancelVisit" wire:loading.attr="disabled" wire:target="cancelVisit">
                                    {{ __('Besuch absagen') }}
                                </button>
                            @endif
                        </div>
                    </section>
                @endif

                @if ($showCancelOnlyCard)
                    <section data-testid="visit-cancel-card" class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
                        <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Termin absagen') }}</h2>

                        <button type="button" class="btn btn-outline w-full rounded-xl" wire:click="cancelVisit" wire:loading.attr="disabled" wire:target="cancelVisit">
                            {{ __('Besuch absagen') }}
                        </button>
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
