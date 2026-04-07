<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
            <div>
                <h1 class="bp-screen-title">{{ $visit->title ?: __('Besuch') }}</h1>
            </div>

            <div class="bp-screen-actions">
                <a href="{{ route('portal.visits.edit', $visit) }}" class="btn btn-primary btn-sm rounded-xl">
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

        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Titel / Anlass') }}</div>
                            <div class="mt-1 text-lg font-semibold text-base-content">{{ $visit->title ?: __('Besuch') }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Host') }}</div>
                            <div class="mt-1 text-lg font-semibold text-base-content">{{ $visit->host?->name ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Verantwortlich / Empfang') }}</div>
                            <div class="mt-1 text-lg font-semibold text-base-content">{{ $visit->responsibleUser?->name ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Beginn') }}</div>
                            <div class="mt-1 text-base text-base-content">{{ optional($visit->scheduled_from)->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Ende') }}</div>
                            <div class="mt-1 text-base text-base-content">{{ optional($visit->scheduled_until)->format('d.m.Y H:i') ?: '-' }}</div>
                        </div>

                        <div>
                            <div class="text-sm text-base-content/60">{{ __('Status') }}</div>
                            <div class="mt-1">
                                <span class="badge badge-outline rounded-full px-3 py-3 text-sm">{{ __($visit->status) }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($visit->notes)
                        <div class="mt-6 border-t border-base-300 pt-6">
                            <div class="text-sm text-base-content/60">{{ __('Notizen') }}</div>
                            <div class="mt-2 text-base leading-7 text-base-content/80">{{ $visit->notes }}</div>
                        </div>
                    @endif
                </div>
            </article>

            <aside class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h2>
                        <span class="badge badge-ghost rounded-full px-3 py-3 text-sm">{{ count($participants) }}</span>
                    </div>

                    <div class="space-y-3">
                        @forelse ($participants as $participant)
                            <div class="rounded-[1.35rem] border border-base-300 bg-base-100 px-4 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-base-content">{{ $participant['name'] }}</span>
                                    <span class="badge {{ $participant['status_class'] }} rounded-full px-3 py-3 text-xs font-semibold">
                                        {{ $participant['status'] }}
                                    </span>
                                </div>

                                <div class="mt-2 text-sm text-base-content/70">
                                    {{ $participant['company'] ?: __('Keine Firma hinterlegt') }}
                                </div>

                                @if ($participant['email'])
                                    <div class="mt-1 text-sm text-base-content/65">{{ $participant['email'] }}</div>
                                @endif

                                @if ($participant['phone'])
                                    <div class="mt-1 text-sm text-base-content/65">{{ $participant['phone'] }}</div>
                                @endif

                                <div class="mt-3 grid gap-2 text-xs text-base-content/60">
                                    <div>
                                        {{ __('Badge') }}:
                                        {{ $participant['badge_printed_at'] ? 
                                            \Carbon\Carbon::parse($participant['badge_printed_at'])->format('d.m.Y H:i') : __('noch nicht gedruckt') }}
                                    </div>
                                    <div>
                                        {{ __('Check-in') }}:
                                        {{ $participant['checked_in_at'] ? 
                                            \Carbon\Carbon::parse($participant['checked_in_at'])->format('d.m.Y H:i') : __('noch nicht erfolgt') }}
                                    </div>
                                    <div>
                                        {{ __('Check-out') }}:
                                        {{ $participant['checked_out_at'] ? 
                                            \Carbon\Carbon::parse($participant['checked_out_at'])->format('d.m.Y H:i') : __('noch nicht erfolgt') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-base-300 bg-base-100 px-4 py-6 text-center text-sm text-base-content/65">
                                {{ __('Es sind noch keine Teilnehmenden zugeordnet.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
