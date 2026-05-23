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
    $showRequiredMarkers = true;
    $requiredMarker = $showRequiredMarkers
        ? new \Illuminate\Support\HtmlString('<span class="bp-required-marker text-error" aria-hidden="true">*</span>')
        : '';
    $visitorContactRequirement = \App\Support\VisitorContactRequirement::current();
    $emailRequiredMarker = in_array($visitorContactRequirement, [
        \App\Support\VisitorContactRequirement::REQUIRE_EMAIL,
        \App\Support\VisitorContactRequirement::REQUIRE_ONE,
    ], true) ? $requiredMarker : '';
    $phoneRequiredMarker = in_array($visitorContactRequirement, [
        \App\Support\VisitorContactRequirement::REQUIRE_PHONE,
        \App\Support\VisitorContactRequirement::REQUIRE_ONE,
    ], true) ? $requiredMarker : '';
    $visitorContactHint = match ($visitorContactRequirement) {
        \App\Support\VisitorContactRequirement::REQUIRE_ONE => __('Bitte E-Mail-Adresse oder Telefonnummer angeben.'),
        \App\Support\VisitorContactRequirement::REQUIRE_EMAIL => __('Bitte E-Mail-Adresse angeben.'),
        \App\Support\VisitorContactRequirement::REQUIRE_PHONE => __('Bitte Telefonnummer angeben.'),
        default => null,
    };
    $canSetAnyVisitStatus = auth()->user()?->can('ViewAny:Visit')
        || auth()->user()?->can('EditAny:Visit')
        || auth()->user()?->can('ViewSite:Visit')
        || auth()->user()?->can('EditSite:Visit');
    $statusOptions = collect(\App\Enums\VisitStatusEnum::options())
        ->when(! $canSetAnyVisitStatus, fn ($options) => $options->only([
            \App\Enums\VisitStatusEnum::Planned->value,
            \App\Enums\VisitStatusEnum::Draft->value,
        ]))
        ->all();

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
            ];
        })
        ->values();

    $siteIdValue = (string) old('site_id', $visit->site_id ?: auth()->user()?->site_id);

    if (! $siteOptions->contains('id', (int) $siteIdValue)) {
        $siteIdValue = (string) ($siteOptions->first()?->id ?? $siteIdValue);
    }

    $hostUserIdValue = (string) old('host_user_id', $visit->host_user_id ?: auth()->id());
    $substituteUserIdValue = (string) old('substitute_user_id', $visit->substitute_user_id);
    $isConfidentialValue = filter_var(old('is_confidential', $visit->is_confidential), FILTER_VALIDATE_BOOL);
    $userCanAccessSelectedSite = fn ($user): bool => $user?->assignedSiteIds()->contains((int) $siteIdValue) ?? false;
    $selectedHost = $hostUsers->firstWhere('id', (int) $hostUserIdValue);

    if (! $selectedHost || ! $userCanAccessSelectedSite($selectedHost)) {
        $hostUserIdValue = (string) ($hostUsers->first(fn ($user) => $userCanAccessSelectedSite($user))?->id ?? '');
    }

    $selectedSubstituteUser = $substituteUsers->firstWhere('id', (int) $substituteUserIdValue);

    if (! $selectedSubstituteUser || ! $userCanAccessSelectedSite($selectedSubstituteUser) || $substituteUserIdValue === $hostUserIdValue) {
        $substituteUserIdValue = '';
    }

    $siteSelectOptions = $siteOptions
        ->map(fn ($site) => [
            'id' => (string) $site->id,
            'label' => $site->name,
        ])
        ->values();

    $hostUserOptions = $hostUsers
        ->map(fn ($user) => [
            'id' => (string) $user->id,
            'label' => $user->fullName,
            'site_ids' => $user->assignedSiteIds()->map(fn ($siteId) => (string) $siteId)->all(),
            'search' => trim(implode(' ', array_filter([
                $user->fullName,
                $user->title,
            ]))),
        ])
        ->values();

    $substituteUserOptions = $substituteUsers
        ->map(fn ($user) => [
            'id' => (string) $user->id,
            'label' => $user->fullName,
            'site_ids' => $user->assignedSiteIds()->map(fn ($siteId) => (string) $siteId)->all(),
            'search' => trim(implode(' ', array_filter([
                $user->fullName,
                $user->title,
            ]))),
        ])
        ->values();

    $visitFormConfig = [
        'beginDate' => $beginDate,
        'beginTime' => $beginTime,
        'endDate' => $endDate,
        'endTime' => $endTime,
        'endDateTouched' => filled($endDate),
        'participants' => $initialParticipants,
        'existingVisitors' => $existingVisitorOptions,
        'siteOptions' => $siteSelectOptions,
        'siteId' => $siteIdValue,
        'hostUserOptions' => $hostUserOptions,
        'substituteUserOptions' => $substituteUserOptions,
        'hostId' => $hostUserIdValue,
        'substituteUserId' => $substituteUserIdValue,
        'recurrenceEnabled' => filter_var($recurrenceEnabledValue, FILTER_VALIDATE_BOOL),
        'recurrenceFrequency' => $recurrenceFrequencyValue,
        'recurrenceEndType' => $recurrenceEndTypeValue,
        'emptyParticipantNameLabel' => __('Ohne Namen'),
        'visitorContactRequirement' => $visitorContactRequirement,
    ];
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

    <div class="grid min-w-0 gap-5" x-data="visitForm({{ \Illuminate\Support\Js::from($visitFormConfig) }})">
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

            @include('portal.visits.partials.details-section')
            @include('portal.visits.partials.participants-section')

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
