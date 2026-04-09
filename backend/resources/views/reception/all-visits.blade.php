<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div><h1 class="bp-screen-title">{{ __('Alle Besuche') }}</h1></div>
        </div>
    </x-slot>

    <div class="bp-content">
        <section class="sticky top-[5.25rem] z-20 mb-3 isolate">
            <div class="grid gap-3 lg:grid-cols-3">
                @foreach ($stats as $stat)
                    <article class="rounded-[1.2rem] border border-base-300 bg-base-100 shadow-sm">
                        <div class="px-4 py-3.5">
                            <div class="text-sm font-medium text-base-content/65">{{ $stat['label'] }}</div>
                            <div class="mt-1 text-[1.35rem] font-semibold leading-none tracking-tight text-base-content">{{ $stat['value'] }}</div>
                            <div class="mt-2 text-sm leading-5 text-base-content/72">{{ $stat['meta'] }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section>
            <article class="card rounded-[1.45rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4 sm:p-5">
                    <form method="GET" action="{{ route('reception.all-visits') }}" class="space-y-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Besuchsliste') }}</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($ranges as $value => $label)
                                    <a href="{{ route('reception.all-visits', ['range' => $value, 'q' => $search ?: null]) }}" class="btn btn-sm rounded-xl {{ $activeRange === $value ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <label class="input input-bordered flex h-11 w-full max-w-3xl items-center gap-2 rounded-xl lg:w-[min(100%,42rem)] lg:max-w-none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-base-content/50"><circle cx="11" cy="11" r="6" /><path d="M20 20l-3.5-3.5" /></svg>
                                <input type="search" name="q" value="{{ $search }}" class="grow" placeholder="{{ __('Nach Titel, Host, Name oder Firma suchen') }}">
                                <input type="hidden" name="range" value="{{ $activeRange }}">
                            </label>

                            <button type="button" class="btn btn-outline rounded-xl lg:shrink-0">{{ __('Ausweise drucken') }}</button>
                        </div>
                    </form>

                    @if ($visits->isEmpty())
                        <div class="mt-4 rounded-[1.25rem] border border-dashed border-base-300 bg-base-100 px-4 py-8 text-center text-sm text-base-content/65">{{ __('Für diesen Zeitraum wurden keine Besuche gefunden.') }}</div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($visits as $visit)
                                <article class="rounded-[1.3rem] border border-base-300 bg-base-100 px-4 py-4 sm:px-5">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0 space-y-2">
                                                <div class="flex flex-wrap items-center gap-2.5">
                                                    <h3 class="text-[1.2rem] font-semibold leading-tight tracking-tight text-base-content">{{ $visit['title'] }}</h3>
                                                    <span class="badge badge-ghost rounded-full px-3 py-2 text-xs font-medium">{{ $visit['time'] }} · {{ $visit['date'] }}</span>
                                                    <span class="badge badge-outline rounded-full px-3 py-2 text-xs font-medium">{{ $visit['status'] }}</span>
                                                </div>
                                                <div class="text-sm text-base-content/65">{{ __('Host') }}: {{ $visit['host'] }} · {{ $visit['participants_count'] }} {{ __('Teilnehmende') }}</div>
                                                @if (!empty($visit['notes']))
                                                    <div class="text-sm text-base-content/75">{{ $visit['notes'] }}</div>
                                                @endif
                                            </div>

                                            <div class="flex flex-wrap gap-2 xl:shrink-0">
                                                <a href="{{ route('portal.visits.show', $visit['id']) }}" class="btn btn-outline btn-sm h-11 min-h-11 rounded-xl px-5">{{ __('Öffnen') }}</a>
                                                <a href="{{ route('portal.visits.edit', $visit['id']) }}" class="btn btn-outline btn-sm h-11 min-h-11 rounded-xl px-5">{{ __('Bearbeiten') }}</a>
                                            </div>
                                        </div>

                                        <div class="rounded-[1.05rem] border border-base-300 bg-base-100 px-3 py-2 sm:px-4">
                                            <div class="space-y-1">
                                                @php
                                                    $rowGrid = 'md:grid-cols-[11rem_11rem_6.25rem_8rem_9rem]';
                                                    $actionButton = 'btn btn-primary btn-sm h-10 min-h-10 w-[9rem] justify-center rounded-xl px-3 text-center';
                                                    $badgeButton = 'btn btn-outline btn-sm h-10 min-h-10 w-[6.25rem] justify-center rounded-xl px-3 text-center';
                                                    $statusPill = 'inline-flex h-10 min-h-10 w-[8rem] items-center justify-center rounded-full border px-3 text-[11px] font-semibold';
                                                @endphp

                                                @foreach ($visit['visible_participants'] as $participant)
                                                    <div x-data="visitParticipantRow(@js($participant))" class="grid gap-x-3 gap-y-2 rounded-lg border-b border-base-200 px-2 py-2 last:border-b-0 md:items-center {{ $rowGrid }}">
                                                        <div class="min-w-0 truncate text-sm font-medium text-base-content" x-text="participant.name"></div>
                                                        <div class="min-w-0 truncate text-sm text-base-content/72" x-text="participant.company || '—'"></div>
                                                        <div class="flex justify-start">
                                                            <button type="button" class="{{ $badgeButton }}" :class="busy || !participant.badge_url ? 'btn-disabled opacity-70' : ''" @click="participant.badge_url ? submitAction(participant.badge_url) : null">{{ __('Ausweis') }}</button>
                                                        </div>
                                                        <div class="flex justify-start">
                                                            <span class="{{ $statusPill }}" :class="participant.status_class" x-text="participant.status_label"></span>
                                                        </div>
                                                        <div class="flex justify-start">
                                                            <button x-show="participant.can_check_in" x-cloak type="button" class="{{ $actionButton }}" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_in_url)" x-text="participant.check_in_label"></button>
                                                            <button x-show="participant.can_check_out" x-cloak type="button" class="{{ $actionButton }}" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_out_url)">{{ __('Check-out') }}</button>
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @if ($visit['hidden_count'] > 0)
                                                    <details class="rounded-xl border border-base-300 bg-base-100">
                                                        <summary class="cursor-pointer list-none px-3 py-3 text-sm font-medium text-base-content/75">+ {{ $visit['hidden_count'] }} {{ __('weitere Teilnehmende') }}</summary>
                                                        <div class="space-y-1 border-t border-base-300 p-3">
                                                            @foreach ($visit['hidden_participants'] as $participant)
                                                                <div x-data="visitParticipantRow(@js($participant))" class="grid gap-x-3 gap-y-2 rounded-lg border-b border-base-200 px-2 py-2 last:border-b-0 md:items-center {{ $rowGrid }}">
                                                                    <div class="min-w-0 truncate text-sm font-medium text-base-content" x-text="participant.name"></div>
                                                                    <div class="min-w-0 truncate text-sm text-base-content/72" x-text="participant.company || '—'"></div>
                                                                    <div class="flex justify-start">
                                                                        <button type="button" class="{{ $badgeButton }}" :class="busy || !participant.badge_url ? 'btn-disabled opacity-70' : ''" @click="participant.badge_url ? submitAction(participant.badge_url) : null">{{ __('Ausweis') }}</button>
                                                                    </div>
                                                                    <div class="flex justify-start">
                                                                        <span class="{{ $statusPill }}" :class="participant.status_class" x-text="participant.status_label"></span>
                                                                    </div>
                                                                    <div class="flex justify-start">
                                                                        <button x-show="participant.can_check_in" x-cloak type="button" class="{{ $actionButton }}" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_in_url)" x-text="participant.check_in_label"></button>
                                                                        <button x-show="participant.can_check_out" x-cloak type="button" class="{{ $actionButton }}" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_out_url)">{{ __('Check-out') }}</button>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        </section>
    </div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            window.visitParticipantRow = function (participant) {
                return {
                    participant: participant,
                    busy: false,
                    async submitAction(url) {
                        if (this.busy || !url) {
                            return;
                        }

                        this.busy = true;

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: new URLSearchParams({ _token: '{{ csrf_token() }}' }).toString(),
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                throw new Error('Request failed');
                            }

                            const data = await response.json();

                            if (data.participant) {
                                this.participant = {
                                    ...this.participant,
                                    ...data.participant,
                                };
                            }
                        } catch (error) {
                            window.location.reload();
                        } finally {
                            this.busy = false;
                        }
                    },
                };
            };
        });
    </script>
@endonce

</x-app-layout>
