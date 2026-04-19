@php
    $participantItems = collect($participants ?? $visit->visitors->map(function ($visitor) use ($visit) {
        $pivot = $visitor->pivot;
        $status = ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];

        if (filled($pivot->checked_out_at)) {
            $status = ['label' => __('Ausgecheckt'), 'class' => 'badge-neutral badge-outline'];
        } elseif (filled($pivot->checked_in_at)) {
            $status = ['label' => __('Eingecheckt'), 'class' => 'badge-success badge-outline'];
        } elseif (filled($pivot->badge_printed_at)) {
            $status = ['label' => __('Badge bereit'), 'class' => 'badge-info badge-outline'];
        } elseif (($visit->status ?? null) === 'draft') {
            $status = ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        } elseif (($visit->status ?? null) === 'canceled') {
            $status = ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        return [
            'name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
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
    $metaChipClass = 'inline-flex min-h-[2.15rem] items-center rounded-full border border-[color:var(--bp-border)] bg-white/90 px-3 text-[0.92rem] text-base-content';
    $detailGridClass = 'grid gap-3 sm:grid-cols-2 xl:grid-cols-3';
    $detailCardClass = 'rounded-2xl border border-[color:var(--bp-border)] bg-white/90 px-4 py-3';
    $detailLabelClass = 'text-[0.75rem] font-medium uppercase tracking-[0.04em] text-base-content/55';
    $detailValueClass = 'mt-1 text-sm font-medium text-base-content';
    $formatParticipantDateTime = function ($value, $fallback = '-') {
        return filled($value)
            ? \Illuminate\Support\Carbon::parse($value)->format('d.m.Y H:i')
            : $fallback;
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">{{ $visit->display_title }}</h1>
            </div>

            <div class="bp-screen-actions">
                <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-outline btn-sm rounded-xl">
                    {{ __('Bearbeiten') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid min-w-0 gap-5">
        @if (session('status'))
            <div class="alert alert-success mb-4 rounded-2xl">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="bp-panel">
            <div class="bp-panel-pad">
                <div class="mb-4 flex flex-wrap gap-2">
                    <span class="badge {{ $statusBadge['class'] }} rounded-full text-sm font-semibold">
                        {{ $statusBadge['label'] }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ optional($visit->scheduled_from)->format('d.m.Y H:i') ?: '-' }}
                        @if ($visit->scheduled_until)
                            · {{ $visit->scheduled_until->format('H:i') }}
                        @endif
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Host') }}: {{ $visit->host?->name ?: '-' }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Empfang') }}: {{ $visit->responsibleUser?->name ?: '-' }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Teilnehmende') }}: {{ $visit->participant_count }}
                    </span>

                    <span class="{{ $metaChipClass }}">
                        {{ __('Offene Badges') }}: {{ $visit->badge_pending_count }}
                    </span>
                </div>

                @if (!empty($visit->notes))
                    <div class="mb-4 rounded-[1.1rem] border border-[color:var(--bp-border)] bg-white/75 px-4 py-3 text-base-content/80">
                        {{ $visit->notes }}
                    </div>
                @endif

                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div class="min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h2>
                            <span class="text-sm text-base-content/60">{{ __('Kompakte Liste, Details bei Bedarf') }}</span>
                        </div>

                        @if ($participantItems->isEmpty())
                            <div class="bp-empty-state text-center">
                                {{ __('Diesem Besuch sind noch keine Teilnehmenden zugeordnet.') }}
                            </div>
                        @else
                            <div class="bp-section-stack">
                                @foreach ($participantItems as $participant)
                                    <details class="bp-subpanel group" @if ($loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bp-subpanel-pad">
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

                                        <div class="border-t border-base-300 bp-panel-pad-tight">
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
                                                    <div class="{{ $detailLabelClass }}">{{ __('Badge') }}</div>
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

                    <div class="grid gap-4">
                        <details class="bp-subpanel" open>
                            <summary class="cursor-pointer list-none bp-subpanel-pad text-base font-semibold text-base-content">
                                {{ __('Termin verschieben') }}
                            </summary>
                            <div class="border-t border-base-300 bp-panel-pad-tight">
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

                        <div class="bp-subpanel bp-subpanel-pad">
                            <div class="mb-3 text-base font-semibold text-base-content">{{ __('Terminstatus') }}</div>

                            @if ($visit->status === 'canceled' || filled($visit->canceled_at))
                                <form method="POST" action="{{ route('portal.visits.reopen', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full">
                                        {{ __('Besuch wieder öffnen') }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('portal.visits.cancel', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full">
                                        {{ __('Besuch absagen') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
