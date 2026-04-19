<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="bp-screen-title">{{ __('Dashboard') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content">
        <section class="bp-stat-grid">
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
                                <article class="rounded-[1.25rem] border border-base-300 bg-base-100 px-4 py-4">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h3 class="text-[1.4rem] font-semibold leading-tight tracking-tight text-base-content">{{ $visit['title'] }}</h3>
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
                                                <div x-data="visitParticipantRow(@js($participant))" class="rounded-xl border border-base-300 bg-base-100 px-3 py-3">
                                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="font-medium text-base-content" x-text="participant.name"></span>
                                                                <span class="badge rounded-full px-2 py-2 text-[11px] font-semibold" :class="participant.status_class" x-text="participant.status_label"></span>
                                                            </div>
                                                            <template x-if="participant.company"><div class="mt-1 text-sm text-base-content/65" x-text="participant.company"></div></template>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <button x-show="participant.can_print_badge" x-cloak type="button" class="btn btn-outline btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.badge_url)">{{ __('Badge') }}</button>
                                                            <button x-show="participant.can_check_in" x-cloak type="button" class="btn btn-primary btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_in_url)" x-text="participant.check_in_label"></button>
                                                            <button x-show="participant.can_check_out" x-cloak type="button" class="btn btn-primary btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_out_url)">{{ __('Check-out') }}</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if ($visit['hidden_count'] > 0)
                                                <details class="rounded-xl border border-base-300 bg-base-100">
                                                    <summary class="cursor-pointer list-none px-3 py-3 text-sm font-medium text-base-content/75">+ {{ $visit['hidden_count'] }} {{ __('weitere Teilnehmende') }}</summary>
                                                    <div class="space-y-2 border-t border-base-300 p-3">
                                                        @foreach ($visit['hidden_participants'] as $participant)
                                                            <div x-data="visitParticipantRow(@js($participant))" class="rounded-xl border border-base-300 bg-base-100 px-3 py-3">
                                                                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                                                    <div class="min-w-0">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            <span class="font-medium text-base-content" x-text="participant.name"></span>
                                                                            <span class="badge rounded-full px-2 py-2 text-[11px] font-semibold" :class="participant.status_class" x-text="participant.status_label"></span>
                                                                        </div>
                                                                        <template x-if="participant.company"><div class="mt-1 text-sm text-base-content/65" x-text="participant.company"></div></template>
                                                                    </div>
                                                                    <div class="flex flex-wrap gap-2">
                                                                        <button x-show="participant.can_print_badge" x-cloak type="button" class="btn btn-outline btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.badge_url)">{{ __('Badge') }}</button>
                                                                        <button x-show="participant.can_check_in" x-cloak type="button" class="btn btn-primary btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_in_url)" x-text="participant.check_in_label"></button>
                                                                        <button x-show="participant.can_check_out" x-cloak type="button" class="btn btn-primary btn-xs rounded-lg" :class="busy ? 'btn-disabled opacity-70' : ''" @click="submitAction(participant.check_out_url)">{{ __('Check-out') }}</button>
                                                                    </div>
                                                                </div>
                                                            </div>
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
