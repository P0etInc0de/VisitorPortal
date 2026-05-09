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
