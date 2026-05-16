<section class="rounded-3xl border border-base-300 bg-base-100 shadow-sm">
    <div class="p-5 sm:p-6">
        <div class="grid gap-6 xl:grid-cols-[1.55fr_0.95fr]">
            <div class="space-y-2">
                <div class="grid gap-5 xl:grid-cols-12">
                    <label class="form-control xl:col-span-12">
                        <div class="label px-0 pb-2">
                            <span class="label-text text-sm font-medium text-base-content">
                                {{ __('Titel_Anlass') }} {{ $requiredMarker }}
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

                    @include('portal.visits.partials.user-select', [
                        'field' => 'host',
                        'name' => 'user_id',
                        'label' => __('Host'),
                        'gridClass' => 'xl:col-span-5',
                        'selectedModel' => 'hostId',
                        'initialValue' => $hostUserIdValue,
                        'placeholder' => __('Host auswählen'),
                        'required' => true,
                        'allowEmpty' => false,
                    ])

                    @include('portal.visits.partials.user-select', [
                        'field' => 'responsible',
                        'name' => 'responsible_user_id',
                        'label' => __('Vertretung'),
                        'gridClass' => 'xl:col-span-4',
                        'selectedModel' => 'responsibleUserId',
                        'initialValue' => $responsibleUserIdValue,
                        'placeholder' => '-',
                        'required' => false,
                        'allowEmpty' => true,
                    ])

                    <label class="form-control xl:col-span-3">
                        <div class="label px-0 pb-2">
                            <span class="label-text text-sm font-medium text-base-content">{{ __('Status') }} {{ $requiredMarker }}</span>
                        </div>
                        <select
                            name="status"
                            class="select select-bordered w-full @error('status') select-error @enderror"
                        >
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $visit->status ?: \App\Enums\VisitStatusEnum::Planned->value) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="xl:col-span-6">
                        <div class="label px-0 pb-2">
                            <span class="label-text text-sm font-medium text-base-content">{{ __('Beginn') }} {{ $requiredMarker }}</span>
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
                            <span class="label-text text-sm font-medium text-base-content">{{ __('Ende') }} {{ $requiredMarker }}</span>
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

                    @include('portal.visits.partials.recurrence-fields')
                </div>
            </div>

            <div class="space-y-5">
                <label class="form-control">
                    <div class="label px-0 pb-2">
                        <span class="label-text text-sm font-medium text-base-content">
                            {{ __('Notizen für Empfang und Ablauf') }}
                        </span>
                    </div>
                    <textarea
                        name="notes"
                        maxlength="1000"
                        class="textarea textarea-bordered h-40 max-h-40 w-full resize-none overflow-y-auto @error('notes') textarea-error @enderror"
                    >{{ old('notes', $visit->notes) }}</textarea>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-base-300 bg-base-200/40 px-4 py-4">
                    <input
                        type="checkbox"
                        name="is_confidential"
                        value="1"
                        class="checkbox checkbox-primary mt-1"
                        @checked($isConfidentialValue)
                    >
                    <span>
                        <span class="block text-sm font-medium text-base-content">{{ __('Vertraulicher Besuch') }}</span>
                        <span class="mt-1 block text-sm text-base-content/65">{{ __('Diese werden nicht automatisch auf Willkommensmonitoren angezeigt.') }}</span>
                    </span>
                </label>
            </div>
        </div>
    </div>
</section>
