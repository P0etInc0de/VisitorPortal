<section class="rounded-3xl border border-base-300 bg-base-100 shadow-sm">
    <div class="p-5 sm:p-6">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-base-content">
                {{ __('Besucher_Teilnehmende') }} {{ $requiredMarker }}
            </h2>
            @if ($visitorContactHint)
                <p class="mt-2 text-sm text-base-content/65">{{ $visitorContactHint }}</p>
            @endif
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
                            placeholder="{{ __('Name oder Firma eingeben') }}"
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
                                            <div class="mt-1 text-sm text-base-content/65" x-text="visitor.company || ''"></div>
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
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }} {{ $requiredMarker }}</span>
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
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }} {{ $requiredMarker }}</span>
                                </div>
                                <input
                                    type="text"
                                    class="input input-bordered w-full"
                                    x-model="draftParticipant.name"
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
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }} {{ $emailRequiredMarker }}</span>
                                </div>
                                <input
                                    type="email"
                                    class="input input-bordered w-full"
                                    x-model="draftParticipant.email"
                                    maxlength="255"
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
                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }} {{ $phoneRequiredMarker }}</span>
                                </div>
                                <input
                                    type="text"
                                    class="input input-bordered w-full"
                                    x-model="draftParticipant.phone"
                                    maxlength="50"
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
