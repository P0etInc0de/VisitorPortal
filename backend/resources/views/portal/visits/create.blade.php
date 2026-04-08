@php
    $existingVisitorOptions = $existingVisitors
        ->map(function ($visitor) {
            return [
                'id' => $visitor->id,
                'first_name' => $visitor->first_name,
                'last_name' => $visitor->last_name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'company' => $visitor->company,
            ];
        })
        ->values();

    $participantSeed = collect($initialParticipants)
        ->map(function ($participant, $index) {
            return [
                'uid' => 'seed-' . $index,
                'visitor_id' => $participant['visitor_id'] ?? null,
                'first_name' => $participant['first_name'] ?? '',
                'last_name' => $participant['last_name'] ?? '',
                'email' => $participant['email'] ?? '',
                'phone' => $participant['phone'] ?? '',
                'company' => $participant['company'] ?? '',
                'expanded' => false,
            ];
        })
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">
                    {{ $isEdit ? __('Besuch bearbeiten') : __('Besuch anlegen') }}
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content">
        @if ($errors->any())
            <div class="alert alert-error mb-4 rounded-2xl">
                <span>{{ __('Bitte prüfen Sie die markierten Felder.') }}</span>
            </div>
        @endif

        <form
            method="POST"
            action="{{ $formAction }}"
            class="space-y-5"
            x-data="visitParticipantComposer({
                participants: {{ Illuminate\Support\Js::from($participantSeed) }},
                existingVisitors: {{ Illuminate\Support\Js::from($existingVisitorOptions) }}
            })"
        >
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <section class="card rounded-[1.5rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="form-control xl:col-span-2">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Titel / Anlass') }}</span>
                            </div>
                            <input
                                type="text"
                                name="title"
                                class="input input-bordered h-11 w-full rounded-xl @error('title') input-error @enderror"
                                value="{{ old('title', $visit->title) }}"
                                placeholder="{{ __('z. B. Technik-Rundgang') }}"
                            >
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Host') }}</span>
                            </div>
                            <select
                                name="user_id"
                                class="select select-bordered h-11 w-full rounded-xl @error('user_id') select-error @enderror"
                            >
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('user_id', $visit->user_id ?: auth()->id()) === $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Verantwortlich / Empfang') }}</span>
                            </div>
                            <select
                                name="responsible_user_id"
                                class="select select-bordered h-11 w-full rounded-xl @error('responsible_user_id') select-error @enderror"
                            >
                                <option value="">{{ __('Optional auswählen') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('responsible_user_id', $visit->responsible_user_id) === $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }}</span>
                            </div>
                            <input
                                type="datetime-local"
                                name="scheduled_from"
                                class="input input-bordered h-11 w-full rounded-xl @error('scheduled_from') input-error @enderror"
                                value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i')) }}"
                            >
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }}</span>
                            </div>
                            <input
                                type="datetime-local"
                                name="scheduled_until"
                                class="input input-bordered h-11 w-full rounded-xl @error('scheduled_until') input-error @enderror"
                                value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i')) }}"
                            >
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Status') }}</span>
                            </div>
                            <select
                                name="status"
                                class="select select-bordered h-11 w-full rounded-xl @error('status') select-error @enderror"
                            >
                                @foreach (['draft' => __('Entwurf'), 'planned' => __('Geplant'), 'completed' => __('Abgeschlossen'), 'canceled' => __('Abgesagt')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $visit->status ?: 'draft') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control xl:col-span-2">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium text-base-content">{{ __('Notizen für Empfang und Ablauf') }}</span>
                            </div>
                            <textarea
                                name="notes"
                                class="textarea textarea-bordered min-h-24 w-full rounded-xl @error('notes') textarea-error @enderror"
                            >{{ old('notes', $visit->notes) }}</textarea>
                        </label>
                    </div>
                </div>
            </section>

            <section class="card rounded-[1.5rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6 space-y-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Besucher / Teilnehmende') }}</h2>
                            <p class="mt-1 text-sm text-base-content/65">
                                {{ __('Bereits hinzugefügte Teilnehmende bleiben kompakt. Neue Personen erfassen Sie nur in einem einzigen Eingabeblock.') }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-4 sm:p-5 space-y-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-base-content">{{ __('Teilnehmende') }}</h3>
                                <p class="mt-1 text-sm text-base-content/65" x-show="participants.length === 0">
                                    {{ __('Noch niemand hinzugefügt. Namen eingeben, vorhandenen Kontakt auswählen oder neue Person anlegen.') }}
                                </p>
                                <p class="mt-1 text-sm text-base-content/65" x-show="participants.length > 0">
                                    <span x-text="participants.length"></span>
                                    {{ __('Teilnehmende aktuell ausgewählt') }}
                                </p>
                            </div>

                            <button type="button" class="btn btn-outline btn-sm rounded-xl" @click="focusComposer()">
                                {{ __('Teilnehmer hinzufügen') }}
                            </button>
                        </div>

                        <div class="space-y-2" x-show="participants.length > 0">
                            <template x-for="(participant, index) in participants" :key="participant.uid">
                                <div class="rounded-xl border border-base-300 bg-base-100 px-3 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <button
                                            type="button"
                                            class="min-w-0 text-left"
                                            @click="participant.expanded = !participant.expanded"
                                        >
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-base-content" x-text="participantDisplayName(participant, index)"></span>
                                                <span
                                                    class="rounded-full border border-base-300 bg-base-100 px-2.5 py-1 text-xs text-base-content/70"
                                                    x-show="participant.company"
                                                    x-text="participant.company"
                                                ></span>
                                                <span
                                                    class="rounded-full border border-base-300 bg-base-100 px-2.5 py-1 text-xs text-base-content/60"
                                                    x-show="participant.visitor_id"
                                                >{{ __('Kontakt') }}</span>
                                                <span
                                                    class="rounded-full border border-primary/20 bg-primary/5 px-2.5 py-1 text-xs text-primary"
                                                    x-show="!participant.visitor_id"
                                                >{{ __('Neu') }}</span>
                                            </div>
                                        </button>

                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs rounded-lg"
                                                @click="participant.expanded = !participant.expanded"
                                            >
                                                <span x-show="!participant.expanded">{{ __('Details') }}</span>
                                                <span x-show="participant.expanded">{{ __('Weniger') }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-ghost btn-xs rounded-lg text-error"
                                                @click="removeParticipant(index)"
                                            >
                                                {{ __('Entfernen') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid gap-2 text-sm text-base-content/70 sm:grid-cols-3" x-show="participant.expanded" x-cloak>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-base-content/45">{{ __('E-Mail') }}</div>
                                            <div class="mt-0.5" x-text="participant.email || '—'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Telefon') }}</div>
                                            <div class="mt-0.5" x-text="participant.phone || '—'"></div>
                                        </div>
                                        <div>
                                            <div class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Firma') }}</div>
                                            <div class="mt-0.5" x-text="participant.company || '—'"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="rounded-xl border border-dashed border-base-300 bg-base-100 p-4 sm:p-5 space-y-4">
                            <div class="space-y-2">
                                <label class="label px-0 pb-0">
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Teilnehmer suchen oder neu anlegen') }}</span>
                                </label>
                                <div class="relative">
                                    <input
                                        x-ref="composerSearch"
                                        type="text"
                                        class="input input-bordered w-full rounded-xl"
                                        x-model="searchQuery"
                                        placeholder="{{ __('Name, Firma oder E-Mail eingeben') }}"
                                        @focus="composerOpen = true"
                                        @input="composerOpen = true"
                                    >

                                    <div
                                        class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-base-300 bg-base-100 shadow-lg"
                                        x-show="composerOpen && searchQuery.trim().length > 0"
                                        x-cloak
                                    >
                                        <div class="max-h-72 overflow-y-auto p-2 space-y-1">
                                            <template x-if="searchResults().length > 0">
                                                <div>
                                                    <template x-for="visitor in searchResults()" :key="'result-' + visitor.id">
                                                        <button
                                                            type="button"
                                                            class="flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-base-200"
                                                            @click="addExistingVisitor(visitor)"
                                                        >
                                                            <div>
                                                                <div class="font-medium text-base-content" x-text="fullName(visitor)"></div>
                                                                <div class="mt-0.5 text-sm text-base-content/65" x-text="visitor.company || visitor.email || '—'"></div>
                                                            </div>
                                                            <span class="text-xs text-base-content/45">{{ __('Auswählen') }}</span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>

                                            <div class="border-t border-base-200 pt-2">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left hover:bg-base-200"
                                                    @click="startNewParticipant()"
                                                >
                                                    <div>
                                                        <div class="font-medium text-base-content">{{ __('Neue Person anlegen') }}</div>
                                                        <div class="mt-0.5 text-sm text-base-content/65" x-text="searchQuery.trim() ? searchQuery : '{{ __('Daten manuell eingeben') }}'"></div>
                                                    </div>
                                                    <span class="text-xs text-base-content/45">{{ __('Neu') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4" x-show="newParticipantMode" x-cloak>
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-base font-semibold text-base-content">{{ __('Neue Person anlegen') }}</h3>
                                        <p class="mt-1 text-sm text-base-content/65">{{ __('Nur dieser eine Eingabeblock ist gleichzeitig offen.') }}</p>
                                    </div>
                                    <button type="button" class="btn btn-ghost btn-sm rounded-lg" @click="cancelNewParticipant()">
                                        {{ __('Abbrechen') }}
                                    </button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Firma') }}</span>
                                        </div>
                                        <input type="text" class="input input-bordered h-11 w-full rounded-xl" x-model="draftParticipant.company">
                                    </label>

                                    <label class="form-control">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }}</span>
                                        </div>
                                        <input type="text" class="input input-bordered h-11 w-full rounded-xl" x-model="draftParticipant.first_name">
                                    </label>

                                    <label class="form-control">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }}</span>
                                        </div>
                                        <input type="text" class="input input-bordered h-11 w-full rounded-xl" x-model="draftParticipant.last_name">
                                    </label>

                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }}</span>
                                        </div>
                                        <input type="email" class="input input-bordered h-11 w-full rounded-xl" x-model="draftParticipant.email">
                                    </label>

                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5">
                                            <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }}</span>
                                        </div>
                                        <input type="text" class="input input-bordered h-11 w-full rounded-xl" x-model="draftParticipant.phone">
                                    </label>
                                </div>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" class="btn btn-outline btn-sm rounded-xl" @click="cancelNewParticipant()">
                                        {{ __('Verwerfen') }}
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm rounded-xl" @click="addDraftParticipant()">
                                        {{ __('Zur Liste hinzufügen') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <template x-for="(participant, index) in participants" :key="participant.uid + '-hidden'">
                <div>
                    <input type="hidden" :name="`participants[${index}][visitor_id]`" :value="participant.visitor_id ?? ''">
                    <input type="hidden" :name="`participants[${index}][first_name]`" :value="participant.first_name ?? ''">
                    <input type="hidden" :name="`participants[${index}][last_name]`" :value="participant.last_name ?? ''">
                    <input type="hidden" :name="`participants[${index}][email]`" :value="participant.email ?? ''">
                    <input type="hidden" :name="`participants[${index}][phone]`" :value="participant.phone ?? ''">
                    <input type="hidden" :name="`participants[${index}][company]`" :value="participant.company ?? ''">
                </div>
            </template>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ $isEdit ? route('portal.visits.show', $visit) : route('dashboard') }}" class="btn btn-outline rounded-xl">
                    {{ __('Abbrechen') }}
                </a>
                <button type="submit" class="btn btn-primary rounded-xl">
                    {{ $isEdit ? __('Besuch aktualisieren') : __('Besuch speichern') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        function visitParticipantComposer(config) {
            return {
                participants: config.participants ?? [],
                existingVisitors: config.existingVisitors ?? [],
                searchQuery: '',
                composerOpen: false,
                newParticipantMode: false,
                draftParticipant: {
                    visitor_id: null,
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    company: '',
                },

                focusComposer() {
                    this.composerOpen = true;
                    this.$nextTick(() => this.$refs.composerSearch?.focus());
                },

                fullName(person) {
                    return [person.first_name, person.last_name].filter(Boolean).join(' ').trim();
                },

                participantDisplayName(participant, index) {
                    const name = this.fullName(participant);
                    return name.length ? name : `{{ __('Teilnehmer') }} ${index + 1}`;
                },

                selectedVisitorIds() {
                    return this.participants
                        .map((participant) => participant.visitor_id)
                        .filter((value) => value !== null && value !== '' && value !== undefined)
                        .map((value) => String(value));
                },

                searchResults() {
                    const query = this.searchQuery.trim().toLowerCase();
                    const selected = this.selectedVisitorIds();

                    if (!query.length) {
                        return [];
                    }

                    return this.existingVisitors
                        .filter((visitor) => !selected.includes(String(visitor.id)))
                        .filter((visitor) => {
                            const haystack = [
                                visitor.first_name,
                                visitor.last_name,
                                visitor.company,
                                visitor.email,
                            ]
                                .filter(Boolean)
                                .join(' ')
                                .toLowerCase();

                            return haystack.includes(query);
                        })
                        .slice(0, 8);
                },

                addExistingVisitor(visitor) {
                    this.participants.push({
                        uid: `existing-${visitor.id}-${Date.now()}`,
                        visitor_id: visitor.id,
                        first_name: visitor.first_name ?? '',
                        last_name: visitor.last_name ?? '',
                        email: visitor.email ?? '',
                        phone: visitor.phone ?? '',
                        company: visitor.company ?? '',
                        expanded: false,
                    });

                    this.searchQuery = '';
                    this.composerOpen = false;
                    this.newParticipantMode = false;
                },

                startNewParticipant() {
                    this.newParticipantMode = true;
                    this.composerOpen = true;

                    const parts = this.searchQuery.trim().split(/\s+/).filter(Boolean);
                    const firstName = parts[0] ?? '';
                    const lastName = parts.length > 1 ? parts.slice(1).join(' ') : '';

                    this.draftParticipant = {
                        visitor_id: null,
                        first_name: firstName,
                        last_name: lastName,
                        email: '',
                        phone: '',
                        company: '',
                    };
                },

                cancelNewParticipant() {
                    this.newParticipantMode = false;
                    this.draftParticipant = {
                        visitor_id: null,
                        first_name: '',
                        last_name: '',
                        email: '',
                        phone: '',
                        company: '',
                    };
                },

                addDraftParticipant() {
                    const hasVisibleData = [
                        this.draftParticipant.first_name,
                        this.draftParticipant.last_name,
                        this.draftParticipant.email,
                        this.draftParticipant.phone,
                        this.draftParticipant.company,
                    ].some((value) => String(value ?? '').trim().length > 0);

                    if (!hasVisibleData) {
                        return;
                    }

                    this.participants.push({
                        uid: `draft-${Date.now()}`,
                        visitor_id: null,
                        first_name: this.draftParticipant.first_name ?? '',
                        last_name: this.draftParticipant.last_name ?? '',
                        email: this.draftParticipant.email ?? '',
                        phone: this.draftParticipant.phone ?? '',
                        company: this.draftParticipant.company ?? '',
                        expanded: false,
                    });

                    this.searchQuery = '';
                    this.cancelNewParticipant();
                    this.composerOpen = false;
                },

                removeParticipant(index) {
                    this.participants.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
