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

    <div class="bp-content bp-visit-show-page">
        @if (session('status'))
            <div class="alert alert-success mb-4 rounded-2xl">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="bp-panel bp-visit-show-shell">
            <div class="sm:p-1">
                <div class="bp-visit-show-meta">
                    <span class="badge {{ $statusBadge['class'] }} rounded-full px-3 py-3 text-sm font-semibold">
                        {{ $statusBadge['label'] }}
                    </span>

                    <span class="bp-visit-show-pill">
                        {{ optional($visit->scheduled_from)->format('d.m.Y H:i') ?: '-' }}
                        @if ($visit->scheduled_until)
                            · {{ $visit->scheduled_until->format('H:i') }}
                        @endif
                    </span>

                    <span class="bp-visit-show-pill">
                        {{ __('Host') }}: {{ $visit->host?->name ?: '-' }}
                    </span>

                    <span class="bp-visit-show-pill">
                        {{ __('Empfang') }}: {{ $visit->responsibleUser?->name ?: '-' }}
                    </span>

                    <span class="bp-visit-show-pill">
                        {{ __('Teilnehmende') }}: {{ $visit->participant_count }}
                    </span>

                    <span class="bp-visit-show-pill">
                        {{ __('Offene Badges') }}: {{ $visit->badge_pending_count }}
                    </span>
                </div>

                @if (!empty($visit->notes))
                    <div class="bp-visit-show-note">
                        {{ $visit->notes }}
                    </div>
                @endif

                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div>
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h2>
                            <span class="text-sm text-base-content/60">{{ __('Kompakte Liste, Details bei Bedarf') }}</span>
                        </div>

                        @if ($participantItems->isEmpty())
                            <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 px-4 py-8 text-center text-base-content/65">
                                {{ __('Diesem Besuch sind noch keine Teilnehmenden zugeordnet.') }}
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($participantItems as $participant)
                                    <details class="bp-visit-show-participant group" @if ($loop->first) open @endif>
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                    <span class="badge {{ $participant['status']['class'] }} rounded-full px-3 py-3 text-xs font-semibold">
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

                                        <div class="bp-visit-show-participant-body">
                                            <div class="bp-visit-show-meta-grid">
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('E-Mail') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">{{ $participant['email'] ?: '–' }}</div>
                                                </div>
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('Telefon') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">{{ $participant['phone'] ?: '–' }}</div>
                                                </div>
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('Firma') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">{{ $participant['company'] ?: '–' }}</div>
                                                </div>
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('Badge') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">
                                                        {{ $formatParticipantDateTime($participant['badge_printed_at'], __('offen')) }}
                                                    </div>
                                                </div>
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('Check-in') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">
                                                        {{ $formatParticipantDateTime($participant['checked_in_at']) }}
                                                    </div>
                                                </div>
                                                <div class="bp-visit-show-meta-box">
                                                    <div class="text-xs text-base-content/60">{{ __('Check-out') }}</div>
                                                    <div class="mt-1 text-sm text-base-content">
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

                    <div class="space-y-3">
                        <details class="bp-visit-show-sidecard" open>
                            <summary class="cursor-pointer list-none px-4 py-3 text-base font-semibold text-base-content">
                                {{ __('Termin verschieben') }}
                            </summary>
                            <div class="bp-visit-show-sidecard-body">
                                <form method="POST" action="{{ route('portal.visits.reschedule', $visit) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')

                                    <label class="form-control w-full">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }}</span>
                                        </div>
                                        <input type="datetime-local" name="scheduled_from" class="input input-bordered h-11 w-full rounded-xl" value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\\TH:i')) }}">
                                    </label>

                                    <label class="form-control w-full">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                                        </div>
                                        <input type="datetime-local" name="scheduled_until" class="input input-bordered h-11 w-full rounded-xl" value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\\TH:i')) }}">
                                    </label>

                                    <button type="submit" class="btn btn-primary w-full rounded-xl">
                                        {{ __('Termin verschieben') }}
                                    </button>
                                </form>
                            </div>
                        </details>

                        <div class="bp-visit-show-sidecard bp-visit-show-status-card">
                            <div class="mb-3 text-base font-semibold text-base-content">{{ __('Terminstatus') }}</div>

                            @if ($visit->status === 'canceled' || filled($visit->canceled_at))
                                <form method="POST" action="{{ route('portal.visits.reopen', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full rounded-xl">
                                        {{ __('Besuch wieder öffnen') }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('portal.visits.cancel', $visit) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline w-full rounded-xl">
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
