@php
    $recurringSeries = $visit->recurringSeries;
    $isRecurringVisit = filled($visit->recurring_visit_series_id) && $recurringSeries;
    $scheduledFromValue = old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i'));
    $scheduledUntilValue = old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i'));
    $recurrenceEnabledValue = old('recurrence_enabled', $isRecurringVisit ? '1' : '0');
    $recurrenceFrequencyValue = old('recurrence_frequency', $recurringSeries?->frequency ?? \App\Models\RecurringVisitSeries::FREQUENCY_WEEKLY);
    $recurrenceIntervalDaysValue = old('recurrence_interval_days', $recurringSeries?->interval_days ?? 2);
    $recurrenceEndTypeValue = old('recurrence_end_type', $recurringSeries?->ends ?? \App\Models\RecurringVisitSeries::END_COUNT);
    $recurrenceEndDateValue = old('recurrence_end_date', optional($recurringSeries?->end_date)->format('Y-m-d'));
    $recurrenceOccurrenceCountValue = old('recurrence_occurrence_count', $recurringSeries?->occurrence_count ?? 2);
    $recurrenceUpdateScopeValue = old('recurrence_update_scope', \App\Models\RecurringVisitSeries::UPDATE_SINGLE);
    $hasInitialParticipants = ! empty($initialParticipants);

    $beginDate = $scheduledFromValue ? substr($scheduledFromValue, 0, 10) : '';
    $beginTime = $scheduledFromValue ? substr($scheduledFromValue, 11, 5) : '';
    $endDate = $scheduledUntilValue ? substr($scheduledUntilValue, 0, 10) : '';
    $endTime = $scheduledUntilValue ? substr($scheduledUntilValue, 11, 5) : '';

    $timeOptions = [];
    for ($hour = 6; $hour <= 20; $hour++) {
        foreach (['00', '30'] as $minute) {
            $timeOptions[] = sprintf('%02d:%s', $hour, $minute);
        }
    }

    $existingVisitorOptions = $existingVisitors
        ->map(function ($visitor) {
            return [
                'id' => $visitor->id,
                'title' => $visitor->title,
                'first_name' => $visitor->first_name,
                'name' => $visitor->name,
                'full_name' => trim(implode(' ', array_filter([
                    $visitor->first_name,
                    $visitor->name,
                ]))) . (filled($visitor->title) ? ', '.$visitor->title : ''),
                'company' => $visitor->company,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
            ];
        })
        ->values();

    $hostUserIdValue = (string) old('user_id', $visit->user_id ?: auth()->id());
    $responsibleUserIdValue = (string) old('responsible_user_id', $visit->responsible_user_id);

    if (! $users->contains('id', (int) $hostUserIdValue)) {
        $hostUserIdValue = (string) ($users->first()?->id ?? '');
    }

    if (! $users->contains('id', (int) $responsibleUserIdValue) || $responsibleUserIdValue === $hostUserIdValue) {
        $responsibleUserIdValue = '';
    }

    $userOptions = $users
        ->map(fn ($user) => [
            'id' => (string) $user->id,
            'label' => $user->fullName,
            'search' => trim(implode(' ', array_filter([
                $user->fullName,
                $user->title,
            ]))),
        ])
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
                    {{ $isEdit ? __('Besuch bearbeiten') : __('Besuch anlegen') }}
                </h1>
            </div>
        </div>
    </x-slot>

    <div
        class="grid min-w-0 gap-5"
        x-data="{
            beginDate: {{ \Illuminate\Support\Js::from($beginDate) }},
            beginTime: {{ \Illuminate\Support\Js::from($beginTime) }},
            endDate: {{ \Illuminate\Support\Js::from($endDate) }},
            endTime: {{ \Illuminate\Support\Js::from($endTime) }},
            endDateTouched: {{ \Illuminate\Support\Js::from(filled($endDate)) }},
            participants: {{ \Illuminate\Support\Js::from($initialParticipants) }},
            existingVisitors: {{ \Illuminate\Support\Js::from($existingVisitorOptions) }},
            userOptions: {{ \Illuminate\Support\Js::from($userOptions) }},
            hostId: {{ \Illuminate\Support\Js::from($hostUserIdValue) }},
            responsibleUserId: {{ \Illuminate\Support\Js::from($responsibleUserIdValue) }},
            userDropdownOpen: null,
            userSearch: {
                host: '',
                responsible: '',
            },
            recurrenceEnabled: {{ \Illuminate\Support\Js::from(filter_var($recurrenceEnabledValue, FILTER_VALIDATE_BOOL)) }},
            recurrenceFrequency: {{ \Illuminate\Support\Js::from($recurrenceFrequencyValue) }},
            recurrenceEndType: {{ \Illuminate\Support\Js::from($recurrenceEndTypeValue) }},
            addingParticipant: false,
            addingNewParticipant: false,
            search: '',
            draftParticipant: {
                visitor_id: null,
                title: '',
                first_name: '',
                name: '',
                email: '',
                phone: '',
                company: '',
                is_existing: false,
                is_open: false,
            },
            composeDateTime(dateValue, timeValue) {
                if (!dateValue || !timeValue) {
                    return '';
                }

                return `${dateValue}T${timeValue}`;
            },
            userLabel(field) {
                const selectedId = field === 'host' ? this.hostId : this.responsibleUserId;
                const user = this.userOptions.find((option) => String(option.id) === String(selectedId));

                return user?.label ?? '';
            },
            openUserDropdown(field) {
                this.userDropdownOpen = field;
                this.userSearch[field] = '';
                this.$nextTick(() => this.$refs[`${field}UserSearch`]?.focus());
            },
            toggleUserDropdown(field) {
                if (this.userDropdownOpen === field) {
                    this.userDropdownOpen = null;

                    return;
                }

                this.openUserDropdown(field);
            },
            selectUser(field, user) {
                if (field === 'host') {
                    this.hostId = String(user.id);

                    if (String(this.responsibleUserId) === String(user.id)) {
                        this.responsibleUserId = '';
                    }
                } else {
                    this.responsibleUserId = String(user.id);
                }

                this.userSearch[field] = '';
                this.userDropdownOpen = null;
            },
            clearUserSearch(field) {
                this.userSearch[field] = '';
                this.$nextTick(() => this.$refs[`${field}UserSearch`]?.focus());
            },
            clearResponsibleUser() {
                this.responsibleUserId = '';
                this.userSearch.responsible = '';
                this.userDropdownOpen = null;
            },
            filteredUsers(field) {
                const term = (this.userSearch[field] ?? '').trim().toLowerCase();
                const excludedId = field === 'host' ? this.responsibleUserId : this.hostId;

                return this.userOptions
                    .filter((user) => String(user.id) !== String(excludedId))
                    .filter((user) => term === '' || String(user.search).toLowerCase().includes(term))
                    .slice(0, 20);
            },
            selectFirstFilteredUser(field) {
                const firstUser = this.filteredUsers(field)[0];

                if (firstUser) {
                    this.selectUser(field, firstUser);
                }
            },
            openAddParticipant() {
                this.addingParticipant = true;
                this.addingNewParticipant = false;
                this.search = '';
                this.resetDraftParticipant();
                this.$nextTick(() => this.$refs.participantSearch?.focus());
            },
            cancelAddParticipant() {
                this.addingParticipant = false;
                this.addingNewParticipant = false;
                this.search = '';
                this.resetDraftParticipant();
            },
            resetDraftParticipant() {
                this.draftParticipant = {
                    visitor_id: null,
                    title: '',
                    first_name: '',
                    name: '',
                    email: '',
                    phone: '',
                    company: '',
                    is_existing: false,
                    is_open: false,
                };
            },
            searchResults() {
                const term = this.search.trim().toLowerCase();

                if (term.length === 0) {
                    return [];
                }

                return this.existingVisitors
                    .filter((visitor) => {
                        const haystack = [
                            visitor.full_name,
                            visitor.title,
                            visitor.company,
                            visitor.email,
                            visitor.phone,
                        ]
                            .filter(Boolean)
                            .join(' ')
                            .toLowerCase();

                        return haystack.includes(term);
                    })
                    .slice(0, 8);
            },
            hasParticipant(visitorId) {
                return this.participants.some((participant) => String(participant.visitor_id ?? '') === String(visitorId));
            },
            selectExistingVisitor(visitor) {
                if (this.hasParticipant(visitor.id)) {
                    return;
                }

                this.participants.push({
                    visitor_id: visitor.id,
                    title: visitor.title ?? '',
                    first_name: visitor.first_name ?? '',
                    name: visitor.name ?? '',
                    email: visitor.email ?? '',
                    phone: visitor.phone ?? '',
                    company: visitor.company ?? '',
                    is_existing: true,
                    is_open: false,
                });

                this.cancelAddParticipant();
            },
            startNewParticipantFromSearch() {
                const term = this.search.trim();
                const parts = term.split(/\s+/).filter(Boolean);

                this.addingNewParticipant = true;
                this.draftParticipant = {
                    visitor_id: null,
                    title: '',
                    first_name: parts[0] ?? '',
                    name: parts.length > 1 ? parts.slice(1).join(' ') : '',
                    email: '',
                    phone: '',
                    company: '',
                    is_existing: false,
                    is_open: true,
                };

                this.$nextTick(() => this.$refs.firstNameInput?.focus());
            },
            addDraftParticipant() {
                if (!this.isDraftParticipantComplete()) {
                    return;
                }

                this.participants.push({
                    visitor_id: null,
                    title: (this.draftParticipant.title ?? '').trim(),
                    first_name: (this.draftParticipant.first_name ?? '').trim(),
                    name: (this.draftParticipant.name ?? '').trim(),
                    email: (this.draftParticipant.email ?? '').trim(),
                    phone: (this.draftParticipant.phone ?? '').trim(),
                    company: (this.draftParticipant.company ?? '').trim(),
                    is_existing: false,
                    is_open: false,
                });

                this.cancelAddParticipant();
            },
            draftParticipantEmailIsValid() {
                const email = (this.draftParticipant.email ?? '').trim();

                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            },
            isDraftParticipantComplete() {
                return Boolean(
                    (this.draftParticipant.first_name ?? '').trim()
                    && (this.draftParticipant.name ?? '').trim()
                    && (this.draftParticipant.company ?? '').trim()
                    && this.draftParticipantEmailIsValid()
                    && (this.draftParticipant.phone ?? '').trim()
                );
            },
            removeParticipant(index) {
                this.participants.splice(index, 1);
            },
            participantName(participant) {
                const title = (participant.title ?? '').trim();
                const firstName = (participant.first_name ?? '').trim();
                const lastName = (participant.name ?? '').trim();
                const fullName = `${firstName} ${lastName}`.trim();

                if (fullName !== '' && title !== '') {
                    return `${fullName}, ${title}`;
                }

                return fullName || title || '{{ __('Ohne Namen') }}';
            },
            participantHasDetails(participant) {
                return Boolean(participant.company || participant.email || participant.phone);
            },
            toggleParticipant(index) {
                this.participants[index].is_open = !this.participants[index].is_open;
            },
            participantHiddenValue(value) {
                return value ?? '';
            },
            syncEndDateFromBegin() {
                if (!this.endDateTouched || !this.endDate) {
                    this.endDate = this.beginDate;
                }
            },
        }"
    >
        <form method="POST" action="{{ $formAction }}" class="grid gap-5">
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <input type="hidden" name="scheduled_from" :value="composeDateTime(beginDate, beginTime)">
            <input type="hidden" name="scheduled_until" :value="composeDateTime(endDate, endTime)">
            <input type="hidden" name="recurrence_enabled" :value="recurrenceEnabled ? '1' : '0'">

            <datalist id="visit-time-options">
                @foreach ($timeOptions as $timeOption)
                    <option value="{{ $timeOption }}"></option>
                @endforeach
            </datalist>

            @if ($errors->any())
                <div class="alert alert-error rounded-2xl">
                    <span>{{ __('Bitte prüfen Sie die markierten Felder.') }}</span>
                </div>
            @endif

            <section class="rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="p-5 sm:p-6">
                    <div class="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
                        <div class="space-y-5">
                            <div class="grid gap-5 xl:grid-cols-12">
                                <label class="form-control xl:col-span-12">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Titel_Anlass') }}
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        name="title"
                                        class="input input-bordered w-full @error('title') input-error @enderror"
                                        value="{{ old('title', $visit->title) }}"
                                        placeholder="{{ __('z. B. Technik-Rundgang') }}"
                                    >
                                </label>

                                <div class="form-control xl:col-span-5">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Host') }}</span>
                                    </div>
                                    <input type="hidden" name="user_id" value="{{ $hostUserIdValue }}" :value="hostId">
                                    <div class="relative" @click.outside="userDropdownOpen === 'host' && (userDropdownOpen = null)">
                                        <button
                                            type="button"
                                            class="input input-bordered flex w-full items-center justify-between gap-3 text-left font-normal @error('user_id') input-error @enderror"
                                            :aria-expanded="userDropdownOpen === 'host'"
                                            @click="toggleUserDropdown('host')"
                                            @keydown.escape="userDropdownOpen = null"
                                        >
                                            <span class="truncate" x-text="userLabel('host') || {{ \Illuminate\Support\Js::from(__('Host auswählen')) }}"></span>
                                            <svg class="h-4 w-4 shrink-0 text-base-content/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div
                                            class="absolute z-40 mt-2 w-full rounded-2xl border border-base-300 bg-base-100 p-2 shadow-xl"
                                            x-cloak
                                            x-show="userDropdownOpen === 'host'"
                                        >
                                            <div class="input input-bordered flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    class="min-w-0 flex-1 bg-transparent outline-none"
                                                    x-ref="hostUserSearch"
                                                    x-model="userSearch.host"
                                                    placeholder="{{ __('Namen suchen') }}"
                                                    @keydown.enter.prevent="selectFirstFilteredUser('host')"
                                                    @keydown.escape.prevent="userDropdownOpen = null"
                                                >
                                                <button
                                                    type="button"
                                                    class="text-base-content/50 hover:text-base-content"
                                                    x-cloak
                                                    x-show="userSearch.host.length > 0"
                                                    @click="clearUserSearch('host')"
                                                    aria-label="{{ __('Suche leeren') }}"
                                                >
                                                    &times;
                                                </button>
                                            </div>

                                            <div class="mt-2 max-h-60 overflow-auto">
                                                <template x-for="user in filteredUsers('host')" :key="user.id">
                                                    <button
                                                        type="button"
                                                        class="w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-base-200"
                                                        :class="String(hostId) === String(user.id) ? 'bg-base-200 font-semibold' : ''"
                                                        @click="selectUser('host', user)"
                                                        x-text="user.label"
                                                    ></button>
                                                </template>
                                                <div class="px-3 py-2 text-sm text-base-content/60" x-show="filteredUsers('host').length === 0">
                                                    {{ __('Keine Treffer') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('user_id')
                                    <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-control xl:col-span-4">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Vertretung') }}
                                        </span>
                                    </div>
                                    <input type="hidden" name="responsible_user_id" value="{{ $responsibleUserIdValue }}" :value="responsibleUserId">
                                    <div class="relative" @click.outside="userDropdownOpen === 'responsible' && (userDropdownOpen = null)">
                                        <button
                                            type="button"
                                            class="input input-bordered flex w-full items-center justify-between gap-3 text-left font-normal @error('responsible_user_id') input-error @enderror"
                                            :aria-expanded="userDropdownOpen === 'responsible'"
                                            @click="toggleUserDropdown('responsible')"
                                            @keydown.escape="userDropdownOpen = null"
                                        >
                                            <span class="truncate" x-text="userLabel('responsible') || {{ \Illuminate\Support\Js::from(__('Optional auswählen')) }}"></span>
                                            <svg class="h-4 w-4 shrink-0 text-base-content/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div
                                            class="absolute z-40 mt-2 w-full rounded-2xl border border-base-300 bg-base-100 p-2 shadow-xl"
                                            x-cloak
                                            x-show="userDropdownOpen === 'responsible'"
                                        >
                                            <div class="input input-bordered flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    class="min-w-0 flex-1 bg-transparent outline-none"
                                                    x-ref="responsibleUserSearch"
                                                    x-model="userSearch.responsible"
                                                    placeholder="{{ __('Namen suchen') }}"
                                                    @keydown.enter.prevent="selectFirstFilteredUser('responsible')"
                                                    @keydown.escape.prevent="userDropdownOpen = null"
                                                >
                                                <button
                                                    type="button"
                                                    class="text-base-content/50 hover:text-base-content"
                                                    x-cloak
                                                    x-show="userSearch.responsible.length > 0"
                                                    @click="clearUserSearch('responsible')"
                                                    aria-label="{{ __('Suche leeren') }}"
                                                >
                                                    &times;
                                                </button>
                                            </div>

                                            <div class="mt-2 max-h-60 overflow-auto">
                                                <button
                                                    type="button"
                                                    class="w-full rounded-xl px-3 py-2 text-left text-sm text-base-content/70 hover:bg-base-200"
                                                    @click="clearResponsibleUser()"
                                                >
                                                    {{ __('Keine Vertretung') }}
                                                </button>
                                                <template x-for="user in filteredUsers('responsible')" :key="user.id">
                                                    <button
                                                        type="button"
                                                        class="w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-base-200"
                                                        :class="String(responsibleUserId) === String(user.id) ? 'bg-base-200 font-semibold' : ''"
                                                        @click="selectUser('responsible', user)"
                                                        x-text="user.label"
                                                    ></button>
                                                </template>
                                                <div class="px-3 py-2 text-sm text-base-content/60" x-show="filteredUsers('responsible').length === 0">
                                                    {{ __('Keine Treffer') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error('responsible_user_id')
                                    <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <label class="form-control xl:col-span-3">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Status') }}</span>
                                    </div>
                                    <select
                                        name="status"
                                        class="select select-bordered w-full @error('status') select-error @enderror"
                                    >
                                        @foreach (\App\Enums\VisitStatusEnum::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $visit->status ?: \App\Enums\VisitStatusEnum::Planned->value) === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <div class="xl:col-span-6">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }}</span>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-[1fr_9rem]">
                                        <input
                                            type="date"
                                            class="input input-bordered w-full @error('scheduled_from') input-error @enderror"
                                            x-model="beginDate"
                                            @change="syncEndDateFromBegin()"
                                        >
                                        <input
                                            type="text"
                                            class="input input-bordered w-full @error('scheduled_from') input-error @enderror"
                                            list="visit-time-options"
                                            placeholder="HH:MM"
                                            x-model="beginTime"
                                        >
                                    </div>
                                    @error('scheduled_from')
                                    <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="xl:col-span-6">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-[1fr_9rem]">
                                        <input
                                            type="date"
                                            class="input input-bordered w-full @error('scheduled_until') input-error @enderror"
                                            x-model="endDate"
                                            @change="endDateTouched = true"
                                        >
                                        <input
                                            type="text"
                                            class="input input-bordered w-full @error('scheduled_until') input-error @enderror"
                                            list="visit-time-options"
                                            placeholder="HH:MM"
                                            x-model="endTime"
                                        >
                                    </div>
                                    @error('scheduled_until')
                                    <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if (! $isEdit || $isRecurringVisit)
                                    <div class="xl:col-span-12 rounded-2xl border border-base-300 bg-base-200/40 p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div class="text-sm font-semibold text-base-content">{{ __('Wiederkehrender Termin') }}</div>
                                                <p class="mt-1 text-sm text-base-content/65">
                                                    {{ __('Erzeugt einzelne Termine aus einer gemeinsamen Regel.') }}
                                                </p>
                                            </div>

                                            <input
                                                type="checkbox"
                                                class="toggle toggle-primary"
                                                x-model="recurrenceEnabled"
                                                @disabled($isEdit && $isRecurringVisit)
                                            >
                                        </div>

                                        @if ($isRecurringVisit)
                                            <div class="mt-4 rounded-2xl border border-base-300 bg-base-100 p-4">
                                                <div class="text-sm font-semibold text-base-content">{{ __('Bearbeitungsbereich') }}</div>
                                                <div class="mt-3 grid gap-2 lg:grid-cols-3">
                                                    @foreach ([
                                                        \App\Models\RecurringVisitSeries::UPDATE_SINGLE => __('Nur diesen Termin'),
                                                        \App\Models\RecurringVisitSeries::UPDATE_FUTURE => __('Diesen und zukünftige Termine'),
                                                        \App\Models\RecurringVisitSeries::UPDATE_SERIES => __('Gesamte Serie'),
                                                    ] as $scopeValue => $scopeLabel)
                                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm text-base-content">
                                                            <input
                                                                type="radio"
                                                                name="recurrence_update_scope"
                                                                value="{{ $scopeValue }}"
                                                                class="radio radio-primary radio-sm"
                                                                @checked($recurrenceUpdateScopeValue === $scopeValue)
                                                            >
                                                            <span>{{ $scopeLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <p class="mt-3 text-sm text-base-content/65">
                                                    {{ __('Individuell angepasste Termine bleiben bei Serien- und Zukunftsänderungen geschützt.') }}
                                                </p>
                                            </div>
                                        @else
                                            <input type="hidden" name="recurrence_update_scope" value="{{ \App\Models\RecurringVisitSeries::UPDATE_SINGLE }}">
                                        @endif

                                        <div x-show="recurrenceEnabled" x-cloak style="display: none;" class="mt-4 grid gap-4 lg:grid-cols-12">
                                            <label class="form-control lg:col-span-4">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Wiederholung') }}</span>
                                                </div>
                                                <select
                                                    name="recurrence_frequency"
                                                    x-model="recurrenceFrequency"
                                                    :disabled="!recurrenceEnabled"
                                                    class="select select-bordered w-full @error('recurrence_frequency') select-error @enderror"
                                                >
                                                    @foreach (\App\Models\RecurringVisitSeries::frequencyOptions() as $frequencyValue => $frequencyLabel)
                                                        <option value="{{ $frequencyValue }}" @selected($recurrenceFrequencyValue === $frequencyValue)>{{ $frequencyLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @error('recurrence_frequency')
                                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                                @enderror
                                            </label>

                                            <label x-show="recurrenceFrequency === '{{ \App\Models\RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS }}'" x-cloak style="display: none;" class="form-control lg:col-span-3">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Intervall in Tagen') }}</span>
                                                </div>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="365"
                                                    name="recurrence_interval_days"
                                                    value="{{ $recurrenceIntervalDaysValue }}"
                                                    :disabled="!recurrenceEnabled || recurrenceFrequency !== '{{ \App\Models\RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS }}'"
                                                    class="input input-bordered w-full @error('recurrence_interval_days') input-error @enderror"
                                                >
                                                @error('recurrence_interval_days')
                                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                                @enderror
                                            </label>

                                            <label class="form-control lg:col-span-4">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                                                </div>
                                                <select
                                                    name="recurrence_end_type"
                                                    x-model="recurrenceEndType"
                                                    :disabled="!recurrenceEnabled"
                                                    class="select select-bordered w-full @error('recurrence_end_type') select-error @enderror"
                                                >
                                                    @foreach (\App\Models\RecurringVisitSeries::endOptions() as $endValue => $endLabel)
                                                        <option value="{{ $endValue }}" @selected($recurrenceEndTypeValue === $endValue)>{{ $endLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @error('recurrence_end_type')
                                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                                @enderror
                                            </label>

                                            <label x-show="recurrenceEndType === '{{ \App\Models\RecurringVisitSeries::END_DATE }}'" x-cloak style="display: none;" class="form-control lg:col-span-4">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Bis Datum') }}</span>
                                                </div>
                                                <input
                                                    type="date"
                                                    name="recurrence_end_date"
                                                    value="{{ $recurrenceEndDateValue }}"
                                                    :disabled="!recurrenceEnabled || recurrenceEndType !== '{{ \App\Models\RecurringVisitSeries::END_DATE }}'"
                                                    class="input input-bordered w-full @error('recurrence_end_date') input-error @enderror"
                                                >
                                                @error('recurrence_end_date')
                                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                                @enderror
                                            </label>

                                            <label x-show="recurrenceEndType === '{{ \App\Models\RecurringVisitSeries::END_COUNT }}'" x-cloak style="display: none;" class="form-control lg:col-span-4">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Anzahl Termine insgesamt') }}</span>
                                                </div>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="1000"
                                                    name="recurrence_occurrence_count"
                                                    value="{{ $recurrenceOccurrenceCountValue }}"
                                                    :disabled="!recurrenceEnabled || recurrenceEndType !== '{{ \App\Models\RecurringVisitSeries::END_COUNT }}'"
                                                    class="input input-bordered w-full @error('recurrence_occurrence_count') input-error @enderror"
                                                >
                                                @error('recurrence_occurrence_count')
                                                <div class="mt-2 text-sm text-error">{{ $message }}</div>
                                                @enderror
                                            </label>

                                            <div x-show="recurrenceEndType === '{{ \App\Models\RecurringVisitSeries::END_FOREVER }}'" x-cloak style="display: none;" class="lg:col-span-8 rounded-2xl border border-base-300 bg-base-100 px-4 py-3 text-sm text-base-content/70">
                                                {{ __('Ohne Ende erzeugt Termine fortlaufend bis 30 Monate im Voraus. Der Scheduler füllt neue Termine automatisch nach.') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="form-control h-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Notizen für Empfang und Ablauf') }}
                                    </span>
                                </div>
                                <textarea
                                    name="notes"
                                    class="textarea textarea-bordered h-40 max-h-40 w-full resize-none overflow-y-auto @error('notes') textarea-error @enderror"
                                >{{ old('notes', $visit->notes) }}</textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="p-5 sm:p-6">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-base-content">
                            {{ __('Besucher_Teilnehmende') }}
                        </h2>
                    </div>

                    <div class="mt-4 rounded-2xl border border-base-300 bg-base-100">
                        <div class="grid gap-4 p-4 sm:p-5">
                            <div
                                x-show="participants.length === 0"
                                @if ($hasInitialParticipants) x-cloak style="display: none;" @endif
                                class="rounded-2xl border border-dashed border-base-300 bg-base-100/70 p-4 text-base-content/65"
                            >
                                {{ __('Noch keine Teilnehmenden hinzugefügt.') }}
                            </div>

                            <div class="grid gap-4">
                                <template x-for="(participant, index) in participants" :key="`participant-${index}`">
                                    <div class="rounded-2xl border border-base-300 bg-base-100 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <div class="font-medium text-base-content" x-text="participantName(participant)"></div>
                                                    <template x-if="participant.company">
                                                        <span class="badge badge-outline" x-text="participant.company"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-ghost btn-sm"
                                                    x-show="participantHasDetails(participant)"
                                                    x-cloak
                                                    @click="toggleParticipant(index)"
                                                >
                                                    <span x-text="participant.is_open ? '{{ __('Weniger') }}' : '{{ __('Details') }}'"></span>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-ghost btn-sm text-error hover:bg-error/10"
                                                    @click="removeParticipant(index)"
                                                >
                                                    {{ __('Entfernen') }}
                                                </button>
                                            </div>
                                        </div>

                                        <div x-show="participant.is_open && participantHasDetails(participant)" x-cloak x-collapse class="mt-3 border-t border-base-200 pt-3">
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <template x-if="participant.email">
                                                    <div>
                                                        <div class="text-xs font-medium uppercase tracking-wide text-base-content/55">{{ __('E-Mail') }}</div>
                                                        <div class="mt-1 text-sm text-base-content/80" x-text="participant.email"></div>
                                                    </div>
                                                </template>
                                                <template x-if="participant.phone">
                                                    <div>
                                                        <div class="text-xs font-medium uppercase tracking-wide text-base-content/55">{{ __('Telefon') }}</div>
                                                        <div class="mt-1 text-sm text-base-content/80" x-text="participant.phone"></div>
                                                    </div>
                                                </template>
                                                <template x-if="participant.company">
                                                    <div>
                                                        <div class="text-xs font-medium uppercase tracking-wide text-base-content/55">{{ __('Firma') }}</div>
                                                        <div class="mt-1 text-sm text-base-content/80" x-text="participant.company"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <input type="hidden" :name="`participants[${index}][visitor_id]`" :value="participantHiddenValue(participant.visitor_id)">
                                        <input type="hidden" :name="`participants[${index}][title]`" :value="participantHiddenValue(participant.title)">
                                        <input type="hidden" :name="`participants[${index}][first_name]`" :value="participantHiddenValue(participant.first_name)">
                                        <input type="hidden" :name="`participants[${index}][name]`" :value="participantHiddenValue(participant.name)">
                                        <input type="hidden" :name="`participants[${index}][email]`" :value="participantHiddenValue(participant.email)">
                                        <input type="hidden" :name="`participants[${index}][phone]`" :value="participantHiddenValue(participant.phone)">
                                        <input type="hidden" :name="`participants[${index}][company]`" :value="participantHiddenValue(participant.company)">
                                    </div>
                                </template>
                            </div>

                            <div x-show="addingParticipant" x-cloak style="display: none;" x-collapse class="rounded-2xl border border-dashed border-base-300 bg-base-100/70 p-4 text-base-content/65">
                                <label class="form-control">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Teilnehmer suchen oder neu anlegen') }}
                                        </span>
                                    </div>
                                    <input
                                        x-ref="participantSearch"
                                        type="text"
                                        class="input input-bordered w-full"
                                        x-model="search"
                                        placeholder="{{ __('Name, Firma oder E-Mail eingeben') }}"
                                    >
                                </label>

                                <div x-show="search.trim().length > 0" x-cloak style="display: none;" x-collapse class="mt-3 space-y-2">
                                    <template x-if="searchResults().length > 0">
                                        <div class="rounded-2xl border border-base-300 bg-base-100">
                                            <template x-for="visitor in searchResults()" :key="visitor.id">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-4 border-b border-base-200 px-4 py-3 text-left last:border-b-0 hover:bg-base-200/40 disabled:cursor-not-allowed disabled:opacity-50"
                                                    :disabled="hasParticipant(visitor.id)"
                                                    @click="selectExistingVisitor(visitor)"
                                                >
                                                    <div>
                                                        <div class="font-medium text-base-content" x-text="visitor.full_name"></div>
                                                        <div class="mt-1 text-sm text-base-content/65" x-text="[visitor.company, visitor.email].filter(Boolean).join(' · ')"></div>
                                                    </div>
                                                    <div class="text-xs font-medium uppercase tracking-wide text-base-content/45" x-text="hasParticipant(visitor.id) ? '{{ __('Bereits hinzugefügt') }}' : '{{ __('Auswählen') }}'"></div>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <button
                                        type="button"
                                        class="btn btn-outline"
                                        @click="startNewParticipantFromSearch()"
                                    >
                                        {{ __('Neue Person anlegen') }}
                                    </button>
                                </div>

                                <div x-show="addingNewParticipant" x-cloak style="display: none;" x-collapse class="mt-5 border-t border-base-200 pt-5">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Titel') }}</span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.title"
                                                placeholder="{{ __('z. B. Dr.') }}"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }} <span class="text-error">*</span></span>
                                            </div>
                                            <input
                                                x-ref="firstNameInput"
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.first_name"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }} <span class="text-error">*</span></span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.name"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Firma') }} <span class="text-error">*</span></span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.company"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-6">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }} <span class="text-error">*</span></span>
                                            </div>
                                            <input
                                                type="email"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.email"
                                            >
                                            <div
                                                x-show="(draftParticipant.email || '').trim() && !draftParticipantEmailIsValid()"
                                                x-cloak
                                                style="display: none;"
                                                class="mt-2 text-sm text-error"
                                            >
                                                {{ __('Bitte eine gültige E-Mail-Adresse eintragen.') }}
                                            </div>
                                        </label>

                                        <label class="form-control xl:col-span-6">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }} <span class="text-error">*</span></span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.phone"
                                            >
                                        </label>
                                    </div>

                                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                                        <button type="button" class="btn btn-outline" @click="cancelAddParticipant()">
                                            {{ __('Verwerfen') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            :disabled="!isDraftParticipantComplete()"
                                            @click="addDraftParticipant()"
                                        >
                                            {{ __('Zur Liste hinzufügen') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button
                                    type="button"
                                    class="btn btn-outline"
                                    x-show="!addingParticipant"
                                    @click="openAddParticipant()"
                                >
                                    {{ __('Teilnehmer hinzufügen') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ $isEdit ? route('portal.visits.show', $visit) : route('overview') }}" class="btn btn-outline">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? __('Besuch aktualisieren') : __('Besuch speichern') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
