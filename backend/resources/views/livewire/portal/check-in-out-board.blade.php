@php
    $hostOptions = $hosts
        ->map(fn ($host) => [
            'id' => (string) $host->id,
            'label' => trim(($host->first_name ?? '').' '.($host->name ?? '')),
            'site_ids' => $host->assignedSiteIds()->map(fn ($siteId) => (string) $siteId)->all(),
            'search' => trim(($host->first_name ?? '').' '.($host->name ?? '')),
        ])
        ->values();
    $siteSelectOptions = $siteOptions
        ->map(fn ($site) => [
            'id' => (string) $site->id,
            'label' => $site->name,
        ])
        ->values();
    $contactRequirement = \App\Support\VisitorContactRequirement::current();
    $requiredMarker = new \Illuminate\Support\HtmlString('<span class="text-error" aria-hidden="true">*</span>');
    $walkInEmailMarker = in_array($contactRequirement, [
        \App\Support\VisitorContactRequirement::REQUIRE_EMAIL,
        \App\Support\VisitorContactRequirement::REQUIRE_ONE,
    ], true) ? $requiredMarker : '';
    $walkInPhoneMarker = in_array($contactRequirement, [
        \App\Support\VisitorContactRequirement::REQUIRE_PHONE,
        \App\Support\VisitorContactRequirement::REQUIRE_ONE,
    ], true) ? $requiredMarker : '';
    $walkInEmailRequired = \App\Support\VisitorContactRequirement::requiresEmail($contactRequirement);
    $walkInPhoneRequired = \App\Support\VisitorContactRequirement::requiresPhone($contactRequirement);
    $walkInContactHint = match ($contactRequirement) {
        \App\Support\VisitorContactRequirement::REQUIRE_ONE => __('Bitte E-Mail-Adresse oder Telefonnummer angeben.'),
        \App\Support\VisitorContactRequirement::REQUIRE_EMAIL => __('Bitte E-Mail-Adresse angeben.'),
        \App\Support\VisitorContactRequirement::REQUIRE_PHONE => __('Bitte Telefonnummer angeben.'),
        default => null,
    };
    $checkInWindowLabel = trans_choice(':count Stunde|:count Stunden', $checkInWindowHours, ['count' => $checkInWindowHours]);
@endphp

<div
    x-data="{
        hosts: @js($hostOptions),
        sites: @js($siteSelectOptions),
        walkInSiteId: @entangle('walkInSiteId'),
        walkInHostId: @entangle('walkInHostId'),
        walkInHostOpen: false,
        walkInHostSearch: '',
        init() {
            this.walkInSiteId = String(this.walkInSiteId || this.sites[0]?.id || '');
            this.ensureWalkInHostMatchesSite();
        },
        printBadge(url) {
            if (!url) {
                return;
            }

            this.$refs.badgeActionForm.action = url;
            this.$refs.badgeActionForm.submit();
        },
        walkInHostLabel() {
            return this.hosts.find((host) => String(host.id) === String(this.walkInHostId) && this.walkInHostMatchesSite(host))?.label || '';
        },
        walkInHostMatchesSite(host) {
            const siteIds = (host.site_ids || []).map((siteId) => String(siteId));

            return this.walkInSiteId === '' || siteIds.includes(String(this.walkInSiteId));
        },
        selectWalkInSite(siteId) {
            this.walkInSiteId = String(siteId || '');
            this.$wire.set('walkInSiteId', this.walkInSiteId);
            this.ensureWalkInHostMatchesSite();
        },
        ensureWalkInHostMatchesSite() {
            const selectedHost = this.hosts.find((host) => String(host.id) === String(this.walkInHostId));

            if (!selectedHost || !this.walkInHostMatchesSite(selectedHost)) {
                this.walkInHostId = null;
                this.$wire.set('walkInHostId', null);
            }
        },
        openWalkInHostDropdown() {
            this.walkInHostOpen = true;
            this.walkInHostSearch = '';
            this.$nextTick(() => this.$refs.walkInHostSearch?.focus());
        },
        toggleWalkInHostDropdown() {
            this.walkInHostOpen ? this.walkInHostOpen = false : this.openWalkInHostDropdown();
        },
        filteredWalkInHosts() {
            const term = this.walkInHostSearch.trim().toLowerCase();

            return this.hosts
                .filter((host) => this.walkInHostMatchesSite(host))
                .filter((host) => term === '' || String(host.search).toLowerCase().includes(term))
                .slice(0, 20);
        },
        selectWalkInHost(host) {
            this.walkInHostId = String(host.id);
            this.$wire.set('walkInHostId', String(host.id));
            this.walkInHostSearch = '';
            this.walkInHostOpen = false;
        },
        selectFirstWalkInHost() {
            const firstHost = this.filteredWalkInHosts()[0];

            if (firstHost) {
                this.selectWalkInHost(firstHost);
            }
        },
        clearWalkInHostSearch() {
            this.walkInHostSearch = '';
            this.$nextTick(() => this.$refs.walkInHostSearch?.focus());
        },
    }"
    x-on:print-badge.window="printBadge($event.detail.url)"
>
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
                        <p class="-mt-2 mb-3 text-sm text-base-content/65">{{ __('Eincheckbare Termine bis zu :window im Voraus und aktive Check-outs.', ['window' => $checkInWindowLabel]) }}</p>
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
                            <article wire:key="check-in-out-result-{{ $result['row_key'] }}" class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-lg font-semibold tracking-tight text-base-content">{{ $result['display_name'] }}</h3>
                                            <span class="badge {{ $result['status_class'] }} rounded-full text-xs font-semibold">{{ $result['status_label'] }}</span>
                                        </div>

                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-base-content/70">
                                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                                <span class="min-w-0 truncate">{{ $result['visit_title'] }}</span>
                                                @if ($result['is_recurring'] ?? false)
                                                    <x-recurrence-indicator :modified="(bool) ($result['recurrence_is_modified'] ?? false)" />
                                                @endif
                                            </span>
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

                                        @if (!empty($result['checked_in_label']) || !empty($result['checked_out_label']))
                                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-base-content/55">
                                                @if (!empty($result['checked_in_label']))
                                                    <span>{{ __('Check-in') }}: {{ $result['checked_in_label'] }}</span>
                                                @endif

                                                @if (!empty($result['checked_out_label']))
                                                    <span>{{ __('Check-out') }}: {{ $result['checked_out_label'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($result['can_check_in'])
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm rounded-xl"
                                                wire:click="checkIn({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="checkIn({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
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
                                                wire:target="checkOut({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                                            >
                                                {{ __('Check-out') }}
                                            </button>
                                        @endif

                                        @if ($result['can_print_badge'])
                                            <form method="POST" action="{{ $result['badge_url'] }}" target="check-in-out-badge-download-frame">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="btn btn-outline btn-sm rounded-xl"
                                                    x-on:click="$wire.printBadge({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                                                >
                                                    {{ __('Ausweis') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>

        <div class="lg:sticky lg:top-6 lg:col-span-1">
            <article class="rounded-3xl border border-base-300 bg-base-100 p-6 sm:p-7 shadow-sm">
                <div class="lg:max-h-[calc(100dvh-13.5rem)] lg:overflow-y-auto lg:overscroll-contain lg:-mr-5 lg:pr-5">
                    <div class="mb-4">
                        <h2 class="mb-4 text-xl font-bold text-base-content">{{ __('Walk-in') }}</h2>
                        <p class="mt-1 text-sm text-base-content/70">{{ __('Unangemeldeten Gast schnell anlegen und direkt einchecken.') }}</p>
                    </div>

                    <form wire:submit.prevent="registerWalkIn" class="flex flex-col gap-4">
                        @if ($walkInContactHint)
                            <p class="rounded-2xl bg-base-200/70 px-4 py-3 text-sm text-base-content/70">{{ $walkInContactHint }}</p>
                        @endif

                        <div class="flex flex-col gap-4">
                            @if ($siteOptions->count() > 1)
                                <div>
                                    <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Standort') }} *</span></label>
                                    <select
                                        class="select select-bordered w-full @error('walkInSiteId') select-error @enderror"
                                        wire:model.live="walkInSiteId"
                                        x-model="walkInSiteId"
                                        @change="selectWalkInSite($event.target.value)"
                                    >
                                        @foreach ($siteOptions as $site)
                                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('walkInSiteId')
                                        <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Ansprechpartner') }} *</span></label>
                            <div class="relative" @click.outside="walkInHostOpen = false">
                                <button
                                    type="button"
                                    class="input input-bordered flex w-full items-center justify-between gap-3 text-left font-normal @error('walkInHostId') input-error @enderror"
                                    :aria-expanded="walkInHostOpen"
                                    @click="toggleWalkInHostDropdown()"
                                    @keydown.escape="walkInHostOpen = false"
                                >
                                    <span class="truncate" x-text="walkInHostLabel() || @js(__('Bitte auswählen'))"></span>
                                    <svg class="h-4 w-4 shrink-0 text-base-content/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div
                                    class="absolute z-40 mt-2 w-full rounded-2xl border border-base-300 bg-base-100 p-2 shadow-xl"
                                    x-cloak
                                    x-show="walkInHostOpen"
                                >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            class="input input-bordered w-full pr-10"
                                            x-ref="walkInHostSearch"
                                            x-model="walkInHostSearch"
                                            placeholder="{{ __('Namen suchen') }}"
                                            @keydown.enter.prevent="selectFirstWalkInHost()"
                                            @keydown.escape.prevent="walkInHostOpen = false"
                                        >
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-3 flex items-center text-base-content/50 hover:text-base-content"
                                            x-cloak
                                            x-show="walkInHostSearch.length > 0"
                                            @click="clearWalkInHostSearch()"
                                            aria-label="{{ __('Suche leeren') }}"
                                        >
                                            &times;
                                        </button>
                                    </div>

                                    <div class="mt-2 max-h-60 overflow-auto">
                                        <template x-for="host in filteredWalkInHosts()" :key="host.id">
                                            <button
                                                type="button"
                                                class="w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-base-200"
                                                :class="String(walkInHostId) === String(host.id) ? 'bg-base-200 font-semibold' : ''"
                                                @click="selectWalkInHost(host)"
                                                x-text="host.label"
                                            ></button>
                                        </template>

                                        <div class="px-3 py-2 text-sm text-base-content/60" x-show="filteredWalkInHosts().length === 0">
                                            {{ __('Keine Treffer') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('walkInHostId')
                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Titel') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model="walkIn.title" placeholder="{{ __('z. B. Dr.') }}">
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Vorname') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model="walkIn.first_name" required>
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Nachname') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model="walkIn.name" required>
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Firma') }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model="walkIn.company">
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('E-Mail') }} {{ $walkInEmailMarker }}</span></label>
                            <input type="email" class="input input-bordered w-full" wire:model="walkIn.email" maxlength="255" placeholder="{{ $walkInEmailMarker ? '' : __('optional') }}" @if ($walkInEmailRequired) required @endif>
                        </div>

                        <div>
                            <label class="label px-0 pb-2"><span class="label-text font-medium">{{ __('Telefon') }} {{ $walkInPhoneMarker }}</span></label>
                            <input type="text" class="input input-bordered w-full" wire:model="walkIn.phone" maxlength="50" placeholder="{{ $walkInPhoneMarker ? '' : __('optional') }}" @if ($walkInPhoneRequired) required @endif>
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-base-300 bg-base-200/40 px-4 py-4">
                            <input type="checkbox" wire:model="walkInIsConfidential" class="checkbox checkbox-primary mt-1">
                            <span>
                                <span class="block text-sm font-medium text-base-content">{{ __('Vertraulicher Besuch') }}</span>
                                <span class="mt-1 block text-sm text-base-content/65">{{ __('Wird nicht automatisch am Willkommensmonitor angezeigt.') }}</span>
                            </span>
                        </label>
                    </div>

                        <div class="grid gap-3 pt-2">
                            <button type="submit" class="btn btn-primary w-full rounded-xl whitespace-nowrap" wire:loading.attr="disabled" wire:target="registerWalkIn">
                                {{ __('Anlegen & Check-in') }}
                            </button>

                            <button type="button" class="btn btn-outline w-full rounded-xl whitespace-nowrap" wire:click="registerWalkIn(true)" wire:loading.attr="disabled" wire:target="registerWalkIn">
                                {{ __('Mit Ausweis') }}
                            </button>
                        </div>
                    </form>
                </div>
            </article>
        </div>
    </div>

    <form x-ref="badgeActionForm" method="POST" target="check-in-out-badge-download-frame" class="hidden">
        @csrf
    </form>

    <iframe name="check-in-out-badge-download-frame" class="hidden" tabindex="-1" aria-hidden="true"></iframe>
</div>
