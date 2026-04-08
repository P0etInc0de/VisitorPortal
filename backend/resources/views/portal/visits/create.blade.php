@php
    $existingVisitorOptions = $existingVisitors
        ->map(function ($visitor) {
            return [
                'id' => $visitor->id,
                'label' => trim($visitor->first_name . ' ' . $visitor->last_name),
                'first_name' => $visitor->first_name,
                'last_name' => $visitor->last_name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'company' => $visitor->company,
                'search' => mb_strtolower(trim($visitor->first_name . ' ' . $visitor->last_name . ' ' . ($visitor->company ?? '') . ' ' . ($visitor->email ?? ''))),
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
        <form
            method="POST"
            action="{{ $formAction }}"
            class="space-y-6"
            x-data="visitParticipantsForm({
                initialParticipants: {{ \Illuminate\Support\Js::from($initialParticipants) }},
                existingVisitors: {{ \Illuminate\Support\Js::from($existingVisitorOptions) }}
            })"
        >
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <section class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label class="form-control xl:col-span-2">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Titel / Anlass') }}</span>
                            </div>
                            <input type="text" name="title" class="input input-bordered w-full rounded-xl @error('title') input-error @enderror" value="{{ old('title', $visit->title) }}" placeholder="{{ __('z. B. Technik-Rundgang') }}">
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Host') }}</span>
                            </div>
                            <select name="user_id" class="select select-bordered w-full rounded-xl @error('user_id') select-error @enderror">
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('user_id', $visit->user_id ?: auth()->id()) === $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Verantwortlich / Empfang') }}</span>
                            </div>
                            <select name="responsible_user_id" class="select select-bordered w-full rounded-xl @error('responsible_user_id') select-error @enderror">
                                <option value="">{{ __('Optional auswählen') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((int) old('responsible_user_id', $visit->responsible_user_id) === $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Beginn') }}</span>
                            </div>
                            <input type="datetime-local" name="scheduled_from" class="input input-bordered w-full rounded-xl @error('scheduled_from') input-error @enderror" value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i')) }}">
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Ende') }}</span>
                            </div>
                            <input type="datetime-local" name="scheduled_until" class="input input-bordered w-full rounded-xl @error('scheduled_until') input-error @enderror" value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i')) }}">
                        </label>

                        <label class="form-control">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Status') }}</span>
                            </div>
                            <select name="status" class="select select-bordered w-full rounded-xl @error('status') select-error @enderror">
                                @foreach (['draft' => __('Entwurf'), 'planned' => __('Geplant'), 'completed' => __('Abgeschlossen'), 'canceled' => __('Abgesagt')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $visit->status ?: 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="form-control md:col-span-2 xl:col-span-4">
                            <div class="label px-0 pb-1.5">
                                <span class="label-text text-sm font-medium">{{ __('Notizen für Empfang und Ablauf') }}</span>
                            </div>
                            <textarea name="notes" class="textarea textarea-bordered min-h-24 w-full rounded-xl @error('notes') textarea-error @enderror">{{ old('notes', $visit->notes) }}</textarea>
                        </label>
                    </div>
                </div>
            </section>

            <section class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-base-content">{{ __('Besucher / Teilnehmende') }}</h2>
                            <p class="mt-1 text-sm text-base-content/70">{{ __('Bereits hinzugefügte Teilnehmende bleiben kompakt. Neue Personen werden nur bei Bedarf erfasst.') }}</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm rounded-xl" @click="openDraft()">
                            {{ __('Teilnehmer hinzufügen') }}
                        </button>
                    </div>

                    <div class="rounded-[1.5rem] border border-base-300 bg-base-100 p-4 sm:p-5 space-y-4">
                        <div>
                            <h3 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmende') }}</h3>
                            <p class="mt-1 text-sm text-base-content/70"><span x-text="participants.length"></span> {{ __('Teilnehmende aktuell ausgewählt') }}</p>
                        </div>

                        <template x-if="participants.length === 0">
                            <div class="rounded-xl border border-dashed border-base-300 px-4 py-6 text-sm text-base-content/65">
                                {{ __('Noch keine Teilnehmenden hinzugefügt.') }}
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(participant, index) in participants" :key="participant.key">
                                <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <button type="button" class="min-w-0 flex-1 text-left" @click="participant.is_open = !participant.is_open">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-lg font-medium text-base-content" x-text="participantDisplayName(participant)"></span>
                                                <template x-if="participant.company">
                                                    <span class="badge badge-ghost rounded-full px-3 py-3 text-sm" x-text="participant.company"></span>
                                                </template>
                                                <template x-if="!participant.is_existing">
                                                    <span class="badge badge-primary badge-outline rounded-full px-3 py-3 text-sm">{{ __('Neu') }}</span>
                                                </template>
                                            </div>
                                        </button>

                                        <div class="flex items-center gap-3 shrink-0">
                                            <button type="button" class="text-sm font-medium text-base-content/70 hover:text-base-content" @click="participant.is_open = !participant.is_open">
                                                {{ __('Details') }}
                                            </button>
                                            <button type="button" class="text-sm font-medium text-error hover:opacity-80" @click="removeParticipant(index)">
                                                {{ __('Entfernen') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div x-cloak x-show="participant.is_open" class="mt-3 border-t border-base-200 pt-3 text-sm text-base-content/70">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <span class="font-medium text-base-content">{{ __('E-Mail') }}:</span>
                                                <span x-text="participant.email || '—'"></span>
                                            </div>
                                            <div>
                                                <span class="font-medium text-base-content">{{ __('Telefon') }}:</span>
                                                <span x-text="participant.phone || '—'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" :name="`participants[${index}][visitor_id]`" :value="participant.visitor_id ?? ''">
                                    <input type="hidden" :name="`participants[${index}][first_name]`" :value="participant.first_name ?? ''">
                                    <input type="hidden" :name="`participants[${index}][last_name]`" :value="participant.last_name ?? ''">
                                    <input type="hidden" :name="`participants[${index}][email]`" :value="participant.email ?? ''">
                                    <input type="hidden" :name="`participants[${index}][phone]`" :value="participant.phone ?? ''">
                                    <input type="hidden" :name="`participants[${index}][company]`" :value="participant.company ?? ''">
                                </div>
                            </template>
                        </div>

                        <div class="rounded-[1.5rem] border border-dashed border-base-300 p-4 sm:p-5" x-show="draftOpen" x-cloak @click.outside="closeSuggestions()">
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Teilnehmer suchen oder neu anlegen') }}</h3>
                                </div>

                                <div class="relative">
                                    <input
                                        type="text"
                                        class="input input-bordered w-full rounded-xl"
                                        :placeholder="draft.mode === 'new' ? '{{ __('Neue Person anlegen') }}' : '{{ __('Name, Firma oder E-Mail eingeben') }}'"
                                        x-model="searchTerm"
                                        @input="handleSearchInput()"
                                        @focus="openSuggestionsIfPossible()"
                                    >

                                    <div
                                        x-cloak
                                        x-show="showSuggestions && searchResults.length > 0"
                                        class="absolute left-0 right-0 top-full z-20 mt-2 overflow-hidden rounded-2xl border border-base-300 bg-base-100 shadow-xl"
                                    >
                                        <template x-for="result in searchResults" :key="result.id">
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left hover:bg-base-200/60"
                                                @click="selectExisting(result)"
                                            >
                                                <div class="min-w-0">
                                                    <div class="font-medium text-base-content" x-text="result.label"></div>
                                                    <div class="mt-1 text-sm text-base-content/65" x-text="result.company || result.email || ''"></div>
                                                </div>
                                                <span class="badge badge-ghost rounded-full px-3 py-3 text-xs">{{ __('Vorhanden') }}</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5"><span class="label-text text-sm font-medium">{{ __('Firma') }}</span></div>
                                        <input type="text" class="input input-bordered w-full rounded-xl" x-model="draft.company">
                                    </label>
                                    <label class="form-control">
                                        <div class="label px-0 pb-1.5"><span class="label-text text-sm font-medium">{{ __('Vorname') }}</span></div>
                                        <input type="text" class="input input-bordered w-full rounded-xl" x-model="draft.first_name">
                                    </label>
                                    <label class="form-control">
                                        <div class="label px-0 pb-1.5"><span class="label-text text-sm font-medium">{{ __('Nachname') }}</span></div>
                                        <input type="text" class="input input-bordered w-full rounded-xl" x-model="draft.last_name">
                                    </label>
                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5"><span class="label-text text-sm font-medium">{{ __('E-Mail') }}</span></div>
                                        <input type="email" class="input input-bordered w-full rounded-xl" x-model="draft.email">
                                    </label>
                                    <label class="form-control xl:col-span-2">
                                        <div class="label px-0 pb-1.5"><span class="label-text text-sm font-medium">{{ __('Telefon') }}</span></div>
                                        <input type="text" class="input input-bordered w-full rounded-xl" x-model="draft.phone">
                                    </label>
                                </div>

                                <div class="flex justify-end gap-3 border-t border-base-200 pt-4">
                                    <button type="button" class="btn btn-outline rounded-xl" @click="cancelDraft()">
                                        {{ __('Verwerfen') }}
                                    </button>
                                    <button type="button" class="btn btn-primary rounded-xl" @click="confirmDraft()">
                                        {{ __('Zur Liste hinzufügen') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
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
        function visitParticipantsForm(config) {
            const initialParticipants = (config.initialParticipants || []).map((participant, index) => ({
                key: participant.key ?? `initial-${index}`,
                visitor_id: participant.visitor_id ?? null,
                first_name: participant.first_name ?? '',
                last_name: participant.last_name ?? '',
                email: participant.email ?? '',
                phone: participant.phone ?? '',
                company: participant.company ?? '',
                is_existing: Boolean(participant.is_existing ?? participant.visitor_id),
                is_open: Boolean(participant.is_open ?? false),
            }));

            return {
                participants: initialParticipants,
                existingVisitors: config.existingVisitors || [],
                draftOpen: false,
                searchTerm: '',
                showSuggestions: false,
                searchResults: [],
                draft: {
                    mode: 'new',
                    visitor_id: null,
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    company: '',
                },
                openDraft() {
                    this.resetDraft();
                    this.draftOpen = true;
                    this.$nextTick(() => {
                        const input = this.$root.querySelector('input[x-model="searchTerm"]');
                        if (input) input.focus();
                    });
                },
                resetDraft() {
                    this.searchTerm = '';
                    this.showSuggestions = false;
                    this.searchResults = [];
                    this.draft = {
                        mode: 'new',
                        visitor_id: null,
                        first_name: '',
                        last_name: '',
                        email: '',
                        phone: '',
                        company: '',
                    };
                },
                cancelDraft() {
                    this.draftOpen = false;
                    this.resetDraft();
                },
                closeSuggestions() {
                    this.showSuggestions = false;
                },
                openSuggestionsIfPossible() {
                    if (this.searchTerm.trim().length > 0) {
                        this.updateResults();
                        this.showSuggestions = this.searchResults.length > 0;
                    }
                },
                handleSearchInput() {
                    const value = this.searchTerm.trim();
                    this.draft.first_name = value;
                    this.updateResults();
                    this.showSuggestions = value.length > 0 && this.searchResults.length > 0;
                },
                updateResults() {
                    const term = this.searchTerm.trim().toLowerCase();
                    if (!term) {
                        this.searchResults = [];
                        return;
                    }
                    const selectedIds = this.participants
                        .map(participant => String(participant.visitor_id || ''))
                        .filter(Boolean);

                    this.searchResults = this.existingVisitors
                        .filter(visitor => visitor.search.includes(term) && !selectedIds.includes(String(visitor.id)))
                        .slice(0, 6);
                },
                selectExisting(visitor) {
                    this.participants.push({
                        key: `existing-${visitor.id}-${Date.now()}`,
                        visitor_id: visitor.id,
                        first_name: visitor.first_name ?? '',
                        last_name: visitor.last_name ?? '',
                        email: visitor.email ?? '',
                        phone: visitor.phone ?? '',
                        company: visitor.company ?? '',
                        is_existing: true,
                        is_open: false,
                    });

                    this.cancelDraft();
                },
                confirmDraft() {
                    if (!this.draft.first_name.trim() || !this.draft.last_name.trim()) {
                        alert(@js(__('Bitte mindestens Vorname und Nachname angeben.')));
                        return;
                    }

                    this.participants.push({
                        key: `new-${Date.now()}`,
                        visitor_id: null,
                        first_name: this.draft.first_name.trim(),
                        last_name: this.draft.last_name.trim(),
                        email: this.draft.email.trim(),
                        phone: this.draft.phone.trim(),
                        company: this.draft.company.trim(),
                        is_existing: false,
                        is_open: false,
                    });

                    this.cancelDraft();
                },
                removeParticipant(index) {
                    this.participants.splice(index, 1);
                },
                participantDisplayName(participant) {
                    const fullName = `${participant.first_name ?? ''} ${participant.last_name ?? ''}`.trim();
                    return fullName || @js(__('Neue Person'));
                },
            };
        }
    </script>
</x-app-layout>
