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
        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    @if ($errors->any())
                        <div class="alert alert-error mb-6 rounded-2xl">
                            <span>{{ __('Bitte prüfen Sie die markierten Felder.') }}</span>
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ $formAction }}"
                        class="space-y-8"
                        x-data='{
                            participants: @json($initialParticipants),
                            existingVisitors: @json($existingVisitorOptions),
                            addParticipant() {
                                this.participants.push({
                                    visitor_id: null,
                                    first_name: "",
                                    last_name: "",
                                    email: "",
                                    phone: "",
                                    company: ""
                                });
                            },
                            removeParticipant(index) {
                                if (this.participants.length === 1) {
                                    this.participants[0] = {
                                        visitor_id: null,
                                        first_name: "",
                                        last_name: "",
                                        email: "",
                                        phone: "",
                                        company: ""
                                    };
                                    return;
                                }

                                this.participants.splice(index, 1);
                            },
                            applyExistingVisitor(index, event) {
                                const visitorId = event.target.value;

                                if (!visitorId) {
                                    return;
                                }

                                const visitor = this.existingVisitors.find(item => String(item.id) === String(visitorId));

                                if (visitor) {
                                    this.participants[index] = {
                                        ...visitor,
                                        visitor_id: visitor.id
                                    };
                                }
                            }
                        }'
                    >
                        @csrf
                        @if ($formMethod !== 'POST')
                            @method($formMethod)
                        @endif

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="form-control md:col-span-2">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Titel / Anlass') }}
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    name="title"
                                    class="input input-bordered w-full rounded-2xl @error('title') input-error @enderror"
                                    value="{{ old('title', $visit->title) }}"
                                    placeholder="{{ __('z. B. Technik-Rundgang') }}"
                                >
                            </label>

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Host') }}
                                    </span>
                                </div>
                                <select
                                    name="user_id"
                                    class="select select-bordered w-full rounded-2xl @error('user_id') select-error @enderror"
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

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Verantwortlich / Empfang') }}
                                    </span>
                                </div>
                                <select
                                    name="responsible_user_id"
                                    class="select select-bordered w-full rounded-2xl @error('responsible_user_id') select-error @enderror"
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

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Beginn') }}
                                    </span>
                                </div>
                                <input
                                    type="datetime-local"
                                    name="scheduled_from"
                                    class="input input-bordered w-full rounded-2xl @error('scheduled_from') input-error @enderror"
                                    value="{{ old('scheduled_from', optional($visit->scheduled_from)->format('Y-m-d\TH:i')) }}"
                                >
                            </label>

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Ende') }}
                                    </span>
                                </div>
                                <input
                                    type="datetime-local"
                                    name="scheduled_until"
                                    class="input input-bordered w-full rounded-2xl @error('scheduled_until') input-error @enderror"
                                    value="{{ old('scheduled_until', optional($visit->scheduled_until)->format('Y-m-d\TH:i')) }}"
                                >
                            </label>

                            <label class="form-control w-full">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Status') }}
                                    </span>
                                </div>
                                <select
                                    name="status"
                                    class="select select-bordered w-full rounded-2xl @error('status') select-error @enderror"
                                >
                                    @foreach (['draft' => __('Entwurf'), 'planned' => __('Geplant'), 'completed' => __('Abgeschlossen'), 'canceled' => __('Abgesagt')] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $visit->status ?: 'draft') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="form-control md:col-span-2">
                                <div class="label px-0 pb-2">
                                    <span class="label-text text-sm font-medium text-base-content">
                                        {{ __('Notizen für Empfang und Ablauf') }}
                                    </span>
                                </div>
                                <textarea
                                    name="notes"
                                    class="textarea textarea-bordered min-h-32 w-full rounded-2xl @error('notes') textarea-error @enderror"
                                >{{ old('notes', $visit->notes) }}</textarea>
                            </label>
                        </div>

                        <section class="space-y-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-xl font-semibold tracking-tight text-base-content">
                                        {{ __('Besucher / Teilnehmende') }}
                                    </h2>
                                    <p class="mt-1 text-sm text-base-content/65">
                                        {{ __('Bestehende Besucher auswählen oder neue Personen direkt anlegen.') }}
                                    </p>
                                </div>

                                <button type="button" class="btn btn-outline btn-sm rounded-xl" @click="addParticipant()">
                                    {{ __('Besucher hinzufügen') }}
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(participant, index) in participants" :key="index">
                                    <div class="rounded-[1.5rem] border border-base-300 bg-base-100 p-5">
                                        <div class="mb-4 flex items-center justify-between gap-4">
                                            <h3 class="text-base font-semibold text-base-content" x-text="'{{ __('Teilnehmer') }} ' + (index + 1)"></h3>
                                            <button type="button" class="btn btn-ghost btn-sm rounded-xl" @click="removeParticipant(index)">
                                                {{ __('Entfernen') }}
                                            </button>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <label class="form-control md:col-span-2">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">
                                                        {{ __('Vorhandenen Besucher auswählen') }}
                                                    </span>
                                                </div>
                                                <select
                                                    class="select select-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][visitor_id]`"
                                                    x-model="participant.visitor_id"
                                                    @change="applyExistingVisitor(index, $event)"
                                                >
                                                    <option value="">{{ __('Neuen Besucher anlegen') }}</option>
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

                                            <label class="form-control w-full">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Vorname') }}</span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="input input-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][first_name]`"
                                                    x-model="participant.first_name"
                                                >
                                            </label>

                                            <label class="form-control w-full">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Nachname') }}</span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="input input-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][last_name]`"
                                                    x-model="participant.last_name"
                                                >
                                            </label>

                                            <label class="form-control w-full">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('E-Mail') }}</span>
                                                </div>
                                                <input
                                                    type="email"
                                                    class="input input-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][email]`"
                                                    x-model="participant.email"
                                                >
                                            </label>

                                            <label class="form-control w-full">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Telefon') }}</span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="input input-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][phone]`"
                                                    x-model="participant.phone"
                                                >
                                            </label>

                                            <label class="form-control md:col-span-2">
                                                <div class="label px-0 pb-2">
                                                    <span class="label-text text-sm font-medium text-base-content">{{ __('Firma') }}</span>
                                                </div>
                                                <input
                                                    type="text"
                                                    class="input input-bordered w-full rounded-2xl"
                                                    :name="`participants[${index}][company]`"
                                                    x-model="participant.company"
                                                >
                                            </label>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>

                        <div class="flex justify-end gap-3 border-t border-base-300 pt-6">
                            <a href="{{ $isEdit ? route('portal.visits.show', $visit) : route('dashboard') }}" class="btn btn-outline rounded-2xl">
                                {{ __('Abbrechen') }}
                            </a>
                            <button type="submit" class="btn btn-primary rounded-2xl">
                                {{ $isEdit ? __('Besuch aktualisieren') : __('Besuch speichern') }}
                            </button>
                        </div>
                    </form>
                </div>
            </article>

            <aside class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div class="space-y-5">
                        @foreach ($summary as $item)
                            <div class="flex items-start justify-between gap-4 border-b border-base-200 pb-4 last:border-b-0 last:pb-0">
                                <span class="text-sm text-base-content/65">{{ $item['label'] }}</span>
                                <span class="text-right font-semibold text-base-content">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
