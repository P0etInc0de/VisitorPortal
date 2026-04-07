@php
    $summary = [
        ['label' => __('Titel'), 'value' => old('title', $visit->title ?: '-')],
        ['label' => __('Host'), 'value' => old('user_id') ? $users->firstWhere('id', (int) old('user_id'))?->name : ($visit->host?->name ?? '-')],
        ['label' => __('Datum'), 'value' => old('scheduled_from', optional($visit->scheduled_from)->format('d.m.Y H:i')) ?: '-'],
        ['label' => __('Status'), 'value' => __(old('status', $visit->status ?: 'draft'))],
        ['label' => __('Teilnehmende'), 'value' => count($initialParticipants)],
    ];

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
                'expanded' => empty($participant['visitor_id']),
                'mode' => !empty($participant['visitor_id']) ? 'existing' : 'new',
            ];
        })
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head bp-screen-head-with-actions">
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
            x-data="visitParticipantForm({
                participants: {{ \Illuminate\Support\Js::from($participantSeed) }},
                existingVisitors: {{ \Illuminate\Support\Js::from($existingVisitorOptions) }}
            })"
        >
            @csrf
            @if ($formMethod !== 'POST')
                @method($formMethod)
            @endif

            <section class="card rounded-[1.5rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($summary as $item)
                            <div class="rounded-full border border-base-300 bg-base-100 px-3 py-1.5 text-sm text-base-content/75">
                                <span class="font-medium text-base-content">{{ $item['label'] }}:</span>
                                <span>{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>

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
                <div class="card-body p-5 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight text-base-content">{{ __('Besucher / Teilnehmende') }}</h2>
                            <p class="mt-1 text-sm text-base-content/65">{{ __('Kompakte Liste mit Namen. Details nur bei Bedarf aufklappen.') }}</p>
                        </div>

                        <button type="button" class="btn btn-outline btn-sm rounded-xl" @click="addParticipant()">
                            {{ __('Besucher hinzufügen') }}
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(participant, index) in participants" :key="participant.uid">
                            <section class="rounded-2xl border border-base-300 bg-base-100">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                                    @click="toggleParticipant(index)"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-base-content" x-text="participantSummary(participant, index)"></span>
                                            <span
                                                class="rounded-full border border-base-300 bg-base-100 px-2.5 py-1 text-xs text-base-content/70"
                                                x-show="participant.company"
                                                x-text="participant.company"
                                            ></span>
                                        </div>

                                        <div class="mt-1 text-sm text-base-content/60">
                                            <span x-show="participant.mode === 'existing'">{{ __('Vorhandener Besucher') }}</span>
                                            <span x-show="participant.mode === 'new'">{{ __('Neuer Besucher') }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs rounded-lg"
                                            @click.stop="removeParticipant(index)"
                                        >
                                            {{ __('Entfernen') }}
                                        </button>

                                        <svg
                                            class="h-4 w-4 shrink-0 text-base-content/55 transition-transform"
                                            :class="{ 'rotate-180': participant.expanded }"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                            aria-hidden="true"
                                        >
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>

                                <div
                                    x-show="participant.expanded"
                                    x-transition.opacity.duration.150ms
                                    class="border-t border-base-300 px-4 py-4"
                                >
                                    <div class="mb-4 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-outline btn-xs rounded-lg"
                                            :class="participant.mode === 'existing' ? 'btn-active' : ''"
                                            @click="participant.mode = 'existing'"
                                        >
                                            {{ __('Vorhandenen Besucher wählen') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline btn-xs rounded-lg"
                                            :class="participant.mode === 'new' ? 'btn-active' : ''"
                                            @click="participant.mode = 'new'; participant.visitor_id = null"
                                        >
                                            {{ __('Neue Person anlegen') }}
                                        </button>
                                    </div>

                                    <template x-if="participant.mode === 'existing'">
                                        <div class="space-y-4">
                                            <label class="form-control">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Vorhandenen Besucher auswählen') }}</span>
                                                </div>
                                                <select
                                                    class="select select-bordered h-11 w-full rounded-xl"
                                                    :name="`participants[${index}][visitor_id]`"
                                                    x-model="participant.visitor_id"
                                                    @change="applyExistingVisitor(index, $event)"
                                                >
                                                    <option value="">{{ __('Bitte auswählen') }}</option>
                                                    @foreach ($existingVisitors as $existingVisitor)
                                                        <option value="{{ $existingVisitor->id }}">
                                                            {{ $existingVisitor->first_name }} {{ $existingVisitor->last_name }}
                                                            @if ($existingVisitor->company)
                                                                · {{ $existingVisitor->company }}
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>

                                            <div class="grid gap-3 md:grid-cols-3">
                                                <div class="rounded-xl border border-base-300 bg-base-100 px-3 py-2">
                                                    <div class="text-xs text-base-content/60">{{ __('E-Mail') }}</div>
                                                    <div class="mt-1 text-sm text-base-content" x-text="participant.email || '–'"></div>
                                                </div>
                                                <div class="rounded-xl border border-base-300 bg-base-100 px-3 py-2">
                                                    <div class="text-xs text-base-content/60">{{ __('Telefon') }}</div>
                                                    <div class="mt-1 text-sm text-base-content" x-text="participant.phone || '–'"></div>
                                                </div>
                                                <div class="rounded-xl border border-base-300 bg-base-100 px-3 py-2">
                                                    <div class="text-xs text-base-content/60">{{ __('Firma') }}</div>
                                                    <div class="mt-1 text-sm text-base-content" x-text="participant.company || '–'"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="participant.mode === 'new'">
                                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                            <input type="hidden" :name="`participants[${index}][visitor_id]`" value="">

                                            <label class="form-control xl:col-span-2">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Firma') }}</span>
                                                </div>
                                                <input type="text" class="input input-bordered h-11 w-full rounded-xl" :name="`participants[${index}][company]`" x-model="participant.company">
                                            </label>

                                            <label class="form-control">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }}</span>
                                                </div>
                                                <input type="text" class="input input-bordered h-11 w-full rounded-xl" :name="`participants[${index}][first_name]`" x-model="participant.first_name">
                                            </label>

                                            <label class="form-control">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }}</span>
                                                </div>
                                                <input type="text" class="input input-bordered h-11 w-full rounded-xl" :name="`participants[${index}][last_name]`" x-model="participant.last_name">
                                            </label>

                                            <label class="form-control xl:col-span-2">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }}</span>
                                                </div>
                                                <input type="email" class="input input-bordered h-11 w-full rounded-xl" :name="`participants[${index}][email]`" x-model="participant.email">
                                            </label>

                                            <label class="form-control xl:col-span-2">
                                                <div class="label px-0 pb-1.5">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }}</span>
                                                </div>
                                                <input type="text" class="input input-bordered h-11 w-full rounded-xl" :name="`participants[${index}][phone]`" x-model="participant.phone">
                                            </label>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3 border-t border-base-300 pt-4">
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
        function visitParticipantForm({ participants, existingVisitors }) {
            return {
                participants,
                existingVisitors,
                addParticipant() {
                    this.participants.push({
                        uid: `new-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        visitor_id: null,
                        first_name: '',
                        last_name: '',
                        email: '',
                        phone: '',
                        company: '',
                        expanded: true,
                        mode: 'new',
                    });
                },
                removeParticipant(index) {
                    if (this.participants.length === 1) {
                        this.participants[0] = {
                            uid: this.participants[0].uid,
                            visitor_id: null,
                            first_name: '',
                            last_name: '',
                            email: '',
                            phone: '',
                            company: '',
                            expanded: true,
                            mode: 'new',
                        };
                        return;
                    }

                    this.participants.splice(index, 1);
                },
                toggleParticipant(index) {
                    this.participants[index].expanded = !this.participants[index].expanded;
                },
                applyExistingVisitor(index, event) {
                    const visitorId = event.target.value;

                    if (!visitorId) {
                        return;
                    }

                    const visitor = this.existingVisitors.find(item => String(item.id) === String(visitorId));

                    if (visitor) {
                        this.participants[index] = {
                            ...this.participants[index],
                            ...visitor,
                            visitor_id: visitor.id,
                            mode: 'existing',
                            expanded: true,
                        };
                    }
                },
                participantSummary(participant, index) {
                    const fullName = [participant.first_name, participant.last_name].filter(Boolean).join(' ').trim();

                    if (fullName.length) {
                        return fullName;
                    }

                    if (participant.visitor_id) {
                        return '{{ __('Vorhandener Besucher') }}';
                    }

                    return `{{ __('Teilnehmer') }} ${index + 1}`;
                },
            };
        }
    </script>
</x-app-layout>
