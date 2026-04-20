<div
    x-data="{
        printBadge(url) {
            if (!url) {
                return;
            }

            this.$refs.badgeActionForm.action = url;
            this.$refs.badgeActionForm.submit();
        },
    }"
    x-on:print-badge.window="printBadge($event.detail.url)"
>
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-base-content">{{ __('Check-In / Out') }}</h1>
        <p class="mt-1.5 text-sm text-base-content/70">{{ __('Suche anstehenden Besucher oder erfasse einen Walk-in direkt am Empfang.') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:items-start">
        <div class="flex flex-col gap-6 lg:col-span-3">
            @if (session('status'))
                <div class="alert alert-success rounded-2xl shadow-sm">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error rounded-2xl shadow-sm">
                    <span>{{ __('Bitte prüfe die Eingaben für den Walk-in.') }}</span>
                </div>
            @endif

            <article class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Suche') }}</h2>

                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    class="input input-bordered w-full text-base"
                    placeholder="{{ __('Name, Titel, Firma, E-Mail oder Besuch suchen') }}"
                    autofocus
                >
            </article>

            <article class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Heute & anstehend') }}</h2>
                        <p class="mt-1 text-sm text-base-content/70">{{ trans_choice(':count Treffer|:count Treffer', count($results), ['count' => count($results)]) }}</p>
                    </div>
                </div>

                @if ($results->isEmpty())
                    <div class="rounded-2xl border border-dashed border-base-300 bg-base-100/70 px-4 py-8 text-sm text-base-content/65">
                        {{ __('Keine passenden Besucher oder Termine gefunden.') }}
                    </div>
                @else
                    <div class="grid gap-3">
                        @foreach ($results as $result)
                            <article class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-semibold tracking-tight text-base-content">{{ $result['display_name'] }}</h3>
                                            <span class="badge {{ $result['status_class'] }} rounded-full text-xs font-semibold">{{ $result['status_label'] }}</span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-base-content/70">
                                            <span>{{ $result['visit_title'] }}</span>
                                            <span>{{ $result['visit_time'] }} · {{ $result['visit_date'] }}</span>
                                            @if (!empty($result['host']))
                                                <span>{{ __('Host') }}: {{ $result['host'] }}</span>
                                            @endif
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-base-content/65">
                                            @if (!empty($result['company']))
                                                <span>{{ $result['company'] }}</span>
                                            @endif
                                            @if (!empty($result['email']))
                                                <span>{{ $result['email'] }}</span>
                                            @endif
                                            @if (!empty($result['phone']))
                                                <span>{{ $result['phone'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($result['can_check_in'])
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm rounded-xl"
                                                wire:click="checkIn({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="checkIn"
                                            >
                                                {{ $result['check_in_label'] }}
                                            </button>
                                        @endif

                                        @if ($result['can_check_out'])
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-sm rounded-xl"
                                                wire:click="checkOut({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="checkOut"
                                            >
                                                {{ __('Check-out') }}
                                            </button>
                                        @endif

                                        <form method="POST" action="{{ $result['badge_url'] }}" target="check-in-out-badge-download-frame">
                                            @csrf
                                            <button type="submit" class="btn btn-outline btn-sm rounded-xl">
                                                {{ __('Ausweis') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>

        <div class="lg:col-span-1 sticky top-6">
            <article class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <div class="mb-4">
                    <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Walk-in') }}</h2>
                    <p class="mt-1 text-sm text-base-content/70">{{ __('Unangemeldeten Gast schnell anlegen und direkt einchecken.') }}</p>
                </div>

                <form wire:submit.prevent="registerWalkIn" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Ansprechpartner') }} *</span></label>
                            <select class="select select-bordered w-full @error('walkInHostId') select-error @enderror" wire:model="walkInHostId" required>
                                <option value="">{{ __('Bitte auswählen') }}</option>
                                @foreach ($hosts as $host)
                                    <option value="{{ $host->id }}">{{ trim(($host->first_name ?? '').' '.($host->name ?? '')) }}</option>
                                @endforeach
                            </select>
                            @error('walkInHostId')
                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Titel') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model.defer="walkIn.title" placeholder="{{ __('z. B. Dr.') }}">
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Vorname') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model.defer="walkIn.first_name" required>
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Nachname') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model.defer="walkIn.name" required>
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Firma') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model.defer="walkIn.company">
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('E-Mail') }}</span></label>
                            <input type="email" class="input input-bordered w-full" wire:model.defer="walkIn.email" placeholder="{{ __('optional') }}">
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Telefon') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model.defer="walkIn.phone" placeholder="{{ __('optional') }}">
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="btn btn-primary flex-1 rounded-xl" wire:loading.attr="disabled" wire:target="registerWalkIn">
                            {{ __('Anlegen & Check-in') }}
                        </button>

                        <button type="button" class="btn btn-outline flex-1 rounded-xl" wire:click="registerWalkIn(true)" wire:loading.attr="disabled" wire:target="registerWalkIn">
                            {{ __('Mit Ausweis') }}
                        </button>
                    </div>
                </form>
            </article>
        </div>
    </div>

    <form x-ref="badgeActionForm" method="POST" target="check-in-out-badge-download-frame" class="hidden">
        @csrf
    </form>

    <iframe name="check-in-out-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
</div>
