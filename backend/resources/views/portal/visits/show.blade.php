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

    <div class="bp-content">
        @if (session('status'))
            <div class="alert alert-success mb-6 rounded-2xl">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-5 flex flex-wrap items-center gap-3">
                        <span class="badge {{ $visitStatus['class'] }} rounded-full px-3 py-3 text-sm font-semibold">
                            {{ $visitStatus['label'] }}
                        </span>

                        <span class="badge badge-ghost rounded-full px-3 py-3 text-sm">
                            {{ optional($visit->scheduled_from)->format('d.m.Y H:i') ?: '-' }}
                            @if ($visit->scheduled_until)
                                · {{ $visit->scheduled_until->format('H:i') }}
                            @endif
                        </span>
                    </div>

                    <dl class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                            <dt class="text-sm text-base-content/65">{{ __('Host') }}</dt>
                            <dd class="mt-1 font-medium text-base-content">{{ $visit->host?->name ?: '-' }}</dd>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                            <dt class="text-sm text-base-content/65">{{ __('Verantwortlich / Empfang') }}</dt>
                            <dd class="mt-1 font-medium text-base-content">{{ $visit->responsibleUser?->name ?: '-' }}</dd>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                            <dt class="text-sm text-base-content/65">{{ __('Teilnehmende') }}</dt>
                            <dd class="mt-1 font-medium text-base-content">{{ $visit->participant_count }}</dd>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                            <dt class="text-sm text-base-content/65">{{ __('Offene Badges') }}</dt>
                            <dd class="mt-1 font-medium text-base-content">{{ $visit->badge_pending_count }}</dd>
                        </div>
                    </dl>

                    @if (!empty($visit->notes))
                        <div class="mt-5 rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                            <div class="text-sm text-base-content/65">{{ __('Notizen') }}</div>
                            <div class="mt-2 whitespace-pre-line text-base text-base-content/85">{{ $visit->notes }}</div>
                        </div>
                    @endif

                    <div class="mt-6">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">
                                {{ __('Teilnehmende') }}
                            </h2>
                        </div>

                        @if ($participants->isEmpty())
                            <div class="rounded-2xl border border-dashed border-base-300 bg-base-100 px-4 py-8 text-center text-base-content/65">
                                {{ __('Diesem Besuch sind noch keine Teilnehmenden zugeordnet.') }}
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($participants as $participant)
                                    <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                                    <span class="badge {{ $participant['status']['class'] }} rounded-full px-3 py-3 text-xs font-semibold">
                                                        {{ $participant['status']['label'] }}
                                                    </span>
                                                </div>

                                                @if (!empty($participant['company']))
                                                    <div class="mt-1 text-sm text-base-content/65">{{ $participant['company'] }}</div>
                                                @endif

                                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-base-content/65">
                                                    @if (!empty($participant['email']))
                                                        <span>{{ $participant['email'] }}</span>
                                                    @endif
                                                    @if (!empty($participant['phone']))
                                                        <span>{{ $participant['phone'] }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="min-w-[220px] space-y-1 text-sm text-base-content/70">
                                                <div>
                                                    {{ __('Badge') }}:
                                                    {{ $participant['badge_printed_at'] ? \Illuminate\Support\Carbon::parse($participant['badge_printed_at'])->format('d.m.Y H:i') : __('offen') }}
                                                </div>
                                                <div>
                                                    {{ __('Check-in') }}:
                                                    {{ $participant['checked_in_at'] ? \Illuminate\Support\Carbon::parse($participant['checked_in_at'])->format('d.m.Y H:i') : '-' }}
                                                </div>
                                                <div>
                                                    {{ __('Check-out') }}:
                                                    {{ $participant['checked_out_at'] ? \Illuminate\Support\Carbon::parse($participant['checked_out_at'])->format('d.m.Y H:i') : '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            <aside class="space-y-6">
                <section class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-5 sm:p-6">
                        <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Termin verschieben') }}
                        </h2>

                        <form method="POST" action="{{ route('portal.visits.reschedule', $visit) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }}</span>
                                </div>
                                <input
                                    type="datetime-local"
                                    name="scheduled_from"
                                    class="input input-bordered w-full rounded-2xl"
                                    value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i')) }}"
                                >
                            </label>

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                                </div>
                                <input
                                    type="datetime-local"
                                    name="scheduled_until"
                                    class="input input-bordered w-full rounded-2xl"
                                    value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i')) }}"
                                >
                            </label>

                            <button type="submit" class="btn btn-primary w-full rounded-xl">
                                {{ __('Termin verschieben') }}
                            </button>
                        </form>
                    </div>
                </section>

                <section class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body p-5 sm:p-6">
                        <h2 class="mb-4 text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Terminstatus') }}
                        </h2>

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
                </section>
            </aside>
        </section>
    </div>
</x-app-layout>
