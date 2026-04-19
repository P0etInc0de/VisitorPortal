@php
    $scheduledFromValue = old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i'));
    $scheduledUntilValue = old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i'));

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
                'first_name' => $visitor->first_name,
                'last_name' => $visitor->last_name,
                'full_name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
                'company' => $visitor->company,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
            ];
        })
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="bp-screen-title">
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
            addingParticipant: false,
            addingNewParticipant: false,
            search: '',
            draftParticipant: {
                visitor_id: null,
                first_name: '',
                last_name: '',
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
                    first_name: '',
                    last_name: '',
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
                    first_name: visitor.first_name ?? '',
                    last_name: visitor.last_name ?? '',
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
                    first_name: parts[0] ?? '',
                    last_name: parts.length > 1 ? parts.slice(1).join(' ') : '',
                    email: '',
                    phone: '',
                    company: '',
                    is_existing: false,
                    is_open: true,
                };

                this.$nextTick(() => this.$refs.firstNameInput?.focus());
            },
            addDraftParticipant() {
                const firstName = (this.draftParticipant.first_name ?? '').trim();
                const lastName = (this.draftParticipant.last_name ?? '').trim();

                if (!firstName || !lastName) {
                    return;
                }

                this.participants.push({
                    visitor_id: null,
                    first_name: firstName,
                    last_name: lastName,
                    email: (this.draftParticipant.email ?? '').trim(),
                    phone: (this.draftParticipant.phone ?? '').trim(),
                    company: (this.draftParticipant.company ?? '').trim(),
                    is_existing: false,
                    is_open: false,
                });

                this.cancelAddParticipant();
            },
            removeParticipant(index) {
                this.participants.splice(index, 1);
            },
            participantName(participant) {
                const firstName = (participant.first_name ?? '').trim();
                const lastName = (participant.last_name ?? '').trim();
                const fullName = `${firstName} ${lastName}`.trim();

                return fullName || '{{ __('Ohne Namen') }}';
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

            <section class="bp-panel">
                <div class="bp-panel-pad">
                    <div class="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
                        <div class="space-y-5">
                            <div class="grid gap-5 xl:grid-cols-12">
                                <label class="form-control xl:col-span-12">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Titel / Anlass') }}
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

                                <label class="form-control xl:col-span-5">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Host') }}</span>
                                    </div>
                                    <select
                                        name="user_id"
                                        class="select select-bordered w-full @error('user_id') select-error @enderror"
                                    >
                                        @foreach ($users as $user)
                                            <option
                                                value="{{ $user->id }}"
                                                @selected((int) old('user_id', $visit->user_id ?: auth()->id()) === $user->id)
                                            >
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="form-control xl:col-span-4">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Verantwortlich / Empfang') }}
                                        </span>
                                    </div>
                                    <select
                                        name="responsible_user_id"
                                        class="select select-bordered w-full @error('responsible_user_id') select-error @enderror"
                                    >
                                        <option value="">{{ __('Optional auswählen') }}</option>
                                        @foreach ($users as $user)
                                            <option
                                                value="{{ $user->id }}"
                                                @selected((int) old('responsible_user_id', $visit->responsible_user_id) === $user->id)
                                            >
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="form-control xl:col-span-3">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Status') }}</span>
                                    </div>
                                    <select
                                        name="status"
                                        class="select select-bordered w-full @error('status') select-error @enderror"
                                    >
                                        @foreach (\App\Enums\VisitStatusEnum::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $visit->status ?: 'draft') === $value)>
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
                                    class="textarea textarea-bordered min-h-[9rem] w-full @error('notes') textarea-error @enderror"
                                >{{ old('notes', $visit->notes) }}</textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bp-panel">
                <div class="bp-panel-pad">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-base-content">
                            {{ __('Besucher / Teilnehmende') }}
                        </h2>
                    </div>

                    <div class="mt-4 bp-subpanel">
                        <div class="bp-panel-pad-tight bp-section-stack">
                            <template x-if="participants.length === 0">
                                <div class="bp-empty-state">
                                    {{ __('Noch keine Teilnehmenden hinzugefügt.') }}
                                </div>
                            </template>

                            <div class="bp-section-stack">
                                <template x-for="(participant, index) in participants" :key="`participant-${index}`">
                                    <div class="bp-subpanel bp-subpanel-pad">
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

                                        <div x-show="participant.is_open && participantHasDetails(participant)" x-collapse class="mt-3 border-t border-base-200 pt-3">
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
                                        <input type="hidden" :name="`participants[${index}][first_name]`" :value="participantHiddenValue(participant.first_name)">
                                        <input type="hidden" :name="`participants[${index}][last_name]`" :value="participantHiddenValue(participant.last_name)">
                                        <input type="hidden" :name="`participants[${index}][email]`" :value="participantHiddenValue(participant.email)">
                                        <input type="hidden" :name="`participants[${index}][phone]`" :value="participantHiddenValue(participant.phone)">
                                        <input type="hidden" :name="`participants[${index}][company]`" :value="participantHiddenValue(participant.company)">
                                    </div>
                                </template>
                            </div>

                            <div x-show="addingParticipant" x-collapse class="bp-empty-state">
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

                                <div x-show="search.trim().length > 0" x-collapse class="mt-3 space-y-2">
                                    <template x-if="searchResults().length > 0">
                                        <div class="bp-subpanel">
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

                                <div x-show="addingNewParticipant" x-collapse class="mt-5 border-t border-base-200 pt-5">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }}</span>
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
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }}</span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.last_name"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-4">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Firma') }}</span>
                                            </div>
                                            <input
                                                type="text"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.company"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-6">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }}</span>
                                            </div>
                                            <input
                                                type="email"
                                                class="input input-bordered w-full"
                                                x-model="draftParticipant.email"
                                            >
                                        </label>

                                        <label class="form-control xl:col-span-6">
                                            <div class="label px-0 pb-2">
                                                <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }}</span>
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
                                            :disabled="!(draftParticipant.first_name || '').trim() || !(draftParticipant.last_name || '').trim()"
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
                <a href="{{ $isEdit ? route('portal.visits.show', $visit) : route('dashboard') }}" class="btn btn-outline">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? __('Besuch aktualisieren') : __('Besuch speichern') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
