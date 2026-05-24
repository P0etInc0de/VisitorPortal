@php
    $participantItems = collect($participants ?? $visit->visitors->map(function ($visitor) use ($visit) {
        $pivot = $visitor->pivot;
        $status = ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];

        if (filled($pivot->checked_out_at)) {
            $status = ['label' => __('Ausgecheckt'), 'class' => 'badge-neutral badge-outline'];
        } elseif (filled($pivot->checked_in_at)) {
            $status = ['label' => __('Eingecheckt'), 'class' => 'badge-success badge-outline'];
        } elseif (filled($pivot->badge_printed_at)) {
            $status = ['label' => __('Ausweis bereit'), 'class' => 'badge-info badge-outline'];
        } elseif (($visit->status ?? null) === 'draft') {
            $status = ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        } elseif (($visit->status ?? null) === 'canceled') {
            $status = ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        return [
            'name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->name ?? '')),
            'title' => $visitor->title,
            'company' => $visitor->company,
            'email' => $visitor->email,
            'phone' => $visitor->phone,
            'badge_printed_at' => $pivot->badge_printed_at,
            'checked_in_at' => $pivot->checked_in_at,
            'checked_out_at' => $pivot->checked_out_at,
            'status' => $status,
        ];
    }));

    $statusBadge = $visitStatus ?? ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    $recurrenceMeta = $recurrenceMeta ?? null;
    $metaChipClass = 'inline-flex min-h-[2.15rem] items-center rounded-full border border-base-300 bg-base-100/90 px-3 text-[0.92rem] text-base-content';
    $detailGridClass = 'grid gap-3 sm:grid-cols-2 xl:grid-cols-3';
    $detailCardClass = 'rounded-2xl border border-base-300 bg-base-100/90 px-4 py-3';
    $detailLabelClass = 'text-[0.75rem] font-medium uppercase tracking-[0.04em] text-base-content/55';
    $detailValueClass = 'mt-1 text-sm font-medium text-base-content';
    $canUpdateVisit = auth()->user()?->can('update', $visit) ?? false;
    $canCancelVisit = auth()->user()?->can('cancel', $visit) ?? false;
    $visitIsCanceled = $visit->status === 'canceled' || filled($visit->canceled_at);
    $showEditAction = $canUpdateVisit;
    $showReopenAction = $canUpdateVisit && $visitIsCanceled;
    $showCancelAction = $canCancelVisit && ! $visitIsCanceled;
    $showCancelOnlyCard = $showCancelAction && ! $canUpdateVisit;
    $showActionCard = $showEditAction || $showReopenAction || ($showCancelAction && ! $showCancelOnlyCard);
    $showSidebar = $recurrenceMeta || $canUpdateVisit || $showActionCard || $showCancelOnlyCard;
    $formatParticipantDateTime = function ($value, $fallback = '-') {
        return filled($value)
            ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y H:i')
            : $fallback;
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ $visit->display_title }}</h1>
            </div>

            @if ($canUpdateVisit)
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline btn-sm rounded-xl">
                        {{ __('Bearbeiten') }}
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

        <div @class([
            'grid min-w-0 gap-5',
            'w-full xl:w-2/3' => ! $showSidebar,
        ])>
        @if (session('status'))
            <div class="alert alert-success mb-4 rounded-2xl">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="rounded-3xl border border-base-300 bg-base-100 shadow-sm">
            <div class="p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap gap-2">
                    <span class="badge {{ $statusBadge['class'] }} rounded-full text-sm font-semibold">
                        {{ $statusBadge['label'] }}
                    </span>

                    @if ($recurrenceMeta)
                        <x-recurrence-indicator :badge="true" :modified="$recurrenceMeta['is_modified']" :label="$recurrenceMeta['badge_label']" />
                    @endif

                    <span class="{{ $metaChipClass }}">
                        {{ optional($visit->scheduled_from)->format('d.m.Y H:i') ?: '-' }}
                        @if ($visit->scheduled_until)
                            · {{ $visit->scheduled_until->format('H:i') }}
                        @endif
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Host') }}: {{ $visit->host?->fullName ?: '-' }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Vertretung') }}: {{ $visit->substituteUser?->fullName ?: '-' }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Teilnehmende') }}: {{ $visit->participant_count }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Offene Ausweise') }}: {{ $visit->badge_pending_count }}
                    </span>
                </div>

                @if (!empty($visit->notes))
                    <div class="mb-4 rounded-[1.1rem] border border-base-300 bg-base-100/75 px-4 py-3 text-base-content/80">
                        {{ $visit->notes }}
                    </div>
                @endif

                <div @class([
                    'grid gap-4',
                    'xl:grid-cols-[minmax(0,1fr)_340px]' => $showSidebar,
                ])>
                    <div class="min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h2>
                            <span class="text-sm text-base-content/60">{{ __('Kompakte Liste, Details bei Bedarf') }}</span>
                        </div>

                        @if ($participantItems->isEmpty())
                            <div class="rounded-2xl border border-dashed border-base-300 bg-base-100/70 p-4 text-center text-base-content/65">
                                {{ __('Diesem Besuch sind noch keine Teilnehmenden zugeordnet.') }}
                            </div>
                        @else
                            <div class="grid gap-4">
                                @foreach ($participantItems as $participant)
                                    <details class="rounded-2xl border border-base-300 bg-base-100 group" @if ($loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                    <span class="badge {{ $participant['status']['class'] }} rounded-full text-xs font-semibold">
                                                        {{ $participant['status']['label'] }}
                                                    </span>
                                                    @if (!empty($participant['company']))
                                                        <span class="text-sm text-base-content/65">{{ $participant['company'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <svg class="h-4 w-4 shrink-0 text-base-content/55 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </summary>

                                        <div class="border-t border-base-300 p-4 sm:p-5">
                                            <div class="{{ $detailGridClass }}">
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('E-Mail') }}</div>
                                                    <div class="{{ $detailValueClass }}">{{ $participant['email'] ?: '–' }}</div>
                                                </div>
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('Telefon') }}</div>
                                                    <div class="{{ $detailValueClass }}">{{ $participant['phone'] ?: '–' }}</div>
                                                </div>
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('Firma') }}</div>
                                                    <div class="{{ $detailValueClass }}">{{ $participant['company'] ?: '–' }}</div>
                                                </div>
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('Ausweis') }}</div>
                                                    <div class="{{ $detailValueClass }}">
                                                        {{ $formatParticipantDateTime($participant['badge_printed_at'], __('offen')) }}
                                                    </div>
                                                </div>
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('Check-in') }}</div>
                                                    <div class="{{ $detailValueClass }}">
                                                        {{ $formatParticipantDateTime($participant['checked_in_at']) }}
                                                    </div>
                                                </div>
                                                <div class="{{ $detailCardClass }}">
                                                    <div class="{{ $detailLabelClass }}">{{ __('Check-out') }}</div>
                                                    <div class="{{ $detailValueClass }}">
                                                        {{ $formatParticipantDateTime($participant['checked_out_at']) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($showSidebar)
                    <div class="grid gap-4">
                        @if ($recurrenceMeta)
                            <div class="rounded-2xl border {{ $recurrenceMeta['is_modified'] ? 'border-warning/40 bg-warning/5' : 'border-base-300 bg-base-100' }} p-4">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div class="text-base font-semibold text-base-content">{{ __('Wiederholung') }}</div>
                                    <x-recurrence-indicator :modified="$recurrenceMeta['is_modified']" :label="$recurrenceMeta['label']" />
                                </div>

                                <div class="grid gap-3">
                                    <div class="{{ $detailCardClass }}">
                                        <div class="{{ $detailLabelClass }}">{{ __('Regel') }}</div>
                                        <div class="{{ $detailValueClass }}">{{ $recurrenceMeta['rule'] }}</div>
                                    </div>

                                    @if (!empty($recurrenceMeta['progress']))
                                        <div class="{{ $detailCardClass }}">
                                            <div class="{{ $detailLabelClass }}">{{ __('Serie') }}</div>
                                            <div class="{{ $detailValueClass }}">{{ $recurrenceMeta['progress'] }}</div>
                                        </div>
                                    @endif

                                    @if ($recurrenceMeta['is_modified'])
                                        <div class="rounded-xl border border-warning/30 bg-warning/10 px-3 py-2 text-sm font-medium text-warning">
                                            {{ $recurrenceMeta['modified_note'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($canUpdateVisit)
                        <details class="rounded-2xl border border-base-300 bg-base-100" open>
                            <summary class="cursor-pointer list-none px-4 py-3 text-base font-semibold text-base-content">
                                {{ __('Termin verschieben') }}
                            </summary>
                            <div class="border-t border-base-300 p-4 sm:p-5">
                                <form method="POST" action="{{ route('portal.visits.reschedule', $visit) }}" class="grid gap-4">
                                    @csrf
                                    @method('PATCH')

                                    <label class="form-control w-full">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }}</span>
                                        </div>
                                        <input type="datetime-local" name="scheduled_from" class="input input-bordered w-full" value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\\TH:i')) }}">
                                    </label>

                                    <label class="form-control w-full">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                                        </div>
                                        <input type="datetime-local" name="scheduled_until" class="input input-bordered w-full" value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\\TH:i')) }}">
                                    </label>

                                    <button type="submit" class="btn btn-primary w-full">
                                        {{ __('Termin verschieben') }}
                                    </button>
                                </form>
                            </div>
                        </details>
                        @endif

                        @if ($showActionCard)
                        <div class="rounded-2xl border border-base-300 bg-base-100 p-4">
                            <div class="mb-3 text-base font-semibold text-base-content">{{ __('Aktionen') }}</div>

                            <div class="grid gap-3">
                                @if ($showEditAction)
                                    <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline w-full">
                                        {{ __('Bearbeiten') }}
                                    </a>
                                @endif

                            @if ($showReopenAction)
                                <form method="POST" action="{{ route('portal.visits.reopen', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full">
                                        {{ __('Besuch wieder öffnen') }}
                                    </button>
                                </form>
                            @endif

                            @if ($showCancelAction && ! $showCancelOnlyCard)
                                <form method="POST" action="{{ route('portal.visits.cancel', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full">
                                        {{ __('Besuch absagen') }}
                                    </button>
                                </form>
                            @endif
                            </div>
                        </div>
                        @endif

                        @if ($showCancelOnlyCard)
                        <div class="rounded-2xl border border-base-300 bg-base-100 p-4">
                            <div class="mb-3 text-base font-semibold text-base-content">{{ __('Termin absagen') }}</div>

                            <form method="POST" action="{{ route('portal.visits.cancel', $visit) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline w-full">
                                    {{ __('Besuch absagen') }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
