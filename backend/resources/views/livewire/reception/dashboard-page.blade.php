<div class="grid min-w-0 gap-5">
    <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        @foreach ($stats as $stat)
            <article class="card rounded-[1.4rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4 sm:p-5">
                    <div class="text-sm font-medium text-base-content/65">{{ $stat['label'] }}</div>
                    <div class="mt-2 text-3xl font-semibold leading-none tracking-tight text-base-content">{{ $stat['value'] }}</div>
                    <div class="mt-2 text-sm text-base-content/70">{{ $stat['meta'] }}</div>
                </div>
            </article>
        @endforeach
    </section>

    <section>
        <article class="card rounded-[1.5rem] border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4 sm:p-5">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Heutige Termine') }}</h2>
                </div>

                @if ($visits->isEmpty())
                    <div class="rounded-[1.25rem] border border-dashed border-base-300 bg-base-100 px-4 py-8 text-center text-sm text-base-content/65">{{ __('Für heute sind aktuell keine Termine geplant.') }}</div>
                @else
                    <div class="space-y-3">
                        @foreach ($visits as $visit)
                            <article wire:key="dashboard-visit-{{ $visit['id'] }}" class="rounded-[1.25rem] border border-base-300 bg-base-100 px-4 py-4">
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-[1.4rem] font-semibold leading-tight tracking-tight text-base-content">{{ $visit['title'] }}</h3>
                                                @if ($visit['is_recurring'] ?? false)
                                                    <x-recurrence-indicator :modified="(bool) ($visit['recurrence_is_modified'] ?? false)" />
                                                @endif
                                                <span class="badge badge-ghost rounded-full px-3 py-2 text-xs font-medium">{{ $visit['time'] }} · {{ $visit['date'] }}</span>
                                            </div>
                                            <div class="mt-1 text-sm text-base-content/65">{{ __('Host') }}: {{ $visit['host'] }}</div>
                                            @if (!empty($visit['notes']))
                                                <div class="mt-1 text-sm text-base-content/70">{{ $visit['notes'] }}</div>
                                            @endif
                                        </div>
                                        <a href="{{ route('portal.visits.show', $visit['id']) }}" class="btn btn-outline btn-sm rounded-xl">{{ __('Termin öffnen') }}</a>
                                    </div>

                                    <div class="space-y-2">
                                        @foreach ($visit['visible_participants'] as $participant)
                                            @include('livewire.reception.partials.dashboard-participant-row', ['visit' => $visit, 'participant' => $participant])
                                        @endforeach
                                        @if ($visit['hidden_count'] > 0)
                                            <details class="rounded-xl border border-base-300 bg-base-100">
                                                <summary class="cursor-pointer list-none px-3 py-3 text-sm font-medium text-base-content/75">+ {{ $visit['hidden_count'] }} {{ __('weitere Teilnehmende') }}</summary>
                                                <div class="space-y-2 border-t border-base-300 p-3">
                                                    @foreach ($visit['hidden_participants'] as $participant)
                                                        @include('livewire.reception.partials.dashboard-participant-row', ['visit' => $visit, 'participant' => $participant])
                                                    @endforeach
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </article>
    </section>

    <iframe name="dashboard-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
</div>
