<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Requests;

use App\Enums\VisitStatusEnum;
use App\Models\RecurringVisitSeries;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Support\VisitorContactRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVisitRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $routeVisit = $this->route('visit');

        $this->merge([
            'site_id' => $this->input('site_id') ?: ($routeVisit instanceof Visit ? $routeVisit->site_id : $this->user()?->site_id),
            'is_confidential' => filter_var($this->input('is_confidential', false), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Visit::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'host_user_id' => ['required', 'integer', 'exists:users,id'],
            'substitute_user_id' => ['nullable', 'integer', 'different:host_user_id', 'exists:users,id'],
            'scheduled_from' => ['required', 'date', 'after_or_equal:1970-01-01'],
            'scheduled_until' => ['required', 'date', 'after:scheduled_from', 'after_or_equal:1970-01-01'],
            'status' => ['required', 'string', Rule::in(VisitStatusEnum::values())],
            'is_confidential' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recurrence_enabled' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['nullable', 'string', Rule::in(RecurringVisitSeries::frequencies())],
            'recurrence_interval_days' => ['nullable', 'integer', 'between:1,365'],
            'recurrence_end_type' => ['nullable', 'string', Rule::in(RecurringVisitSeries::ends())],
            'recurrence_end_date' => ['nullable', 'date'],
            'recurrence_occurrence_count' => ['nullable', 'integer', 'between:1,1000'],
            'recurrence_update_scope' => ['nullable', 'string', Rule::in(RecurringVisitSeries::updateScopes())],
            'recurrence_exclusion_dates' => ['nullable', 'array'],
            'recurrence_exclusion_dates.*' => ['date'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['array'],
            'participants.*.visitor_id' => ['nullable', 'integer', 'exists:visitors,id'],
            'participants.*.title' => ['nullable', 'string', 'max:255'],
            'participants.*.first_name' => ['nullable', 'string', 'max:255'],
            'participants.*.name' => ['nullable', 'string', 'max:255'],
            'participants.*.email' => ['nullable', 'email', 'max:255'],
            'participants.*.phone' => ['nullable', 'string', 'max:50'],
            'participants.*.company' => ['nullable', 'string', 'max:255'],
            'participants.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_from.after_or_equal' => __('Das Startdatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after_or_equal' => __('Das Enddatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after' => __('Das Ende muss nach dem Beginn liegen.'),
            'host_user_id.required' => __('Host ist erforderlich.'),
            'substitute_user_id.different' => __('Vertretung darf nicht identisch mit dem Host sein.'),
            'recurrence_occurrence_count.between' => __('Bitte wählen Sie zwischen 1 und 1000 Terminen insgesamt.'),
            'recurrence_interval_days.between' => __('Bitte wählen Sie ein Intervall zwischen 1 und 365 Tagen.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'participants.*.first_name' => __('Vorname der teilnehmenden Person'),
            'participants.*.name' => __('Nachname der teilnehmenden Person'),
            'participants.*.email' => __('E-Mail der teilnehmenden Person'),
            'participants.*.phone' => __('Telefon der teilnehmenden Person'),
            'participants.*.company' => __('Firma der teilnehmenden Person'),
            'site_id' => __('Standort'),
            'host_user_id' => __('Host'),
            'substitute_user_id' => __('Vertretung'),
            'recurrence_frequency' => __('Wiederholung'),
            'recurrence_interval_days' => __('Intervall in Tagen'),
            'recurrence_end_type' => __('Ende der Wiederholung'),
            'recurrence_end_date' => __('Enddatum der Wiederholung'),
            'recurrence_occurrence_count' => __('Anzahl Termine insgesamt'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateSelectableUsers($validator);
            $this->validateStatusSelection($validator);
            $this->validateParticipantVisibility($validator);
            $this->validateParticipantContactRequirement($validator);

            if ($this->recurrenceEnabled()) {
                $this->validateRecurrence($validator);
            }

            foreach ($this->input('participants', []) as $index => $participant) {
                if (! is_array($participant) || filled($participant['visitor_id'] ?? null)) {
                    continue;
                }

                foreach ($this->requiredInlineParticipantFields() as $field => $label) {
                    if (blank($participant[$field] ?? null)) {
                        $validator->errors()->add(
                            "participants.{$index}.{$field}",
                            __(':attribute ist erforderlich.', ['attribute' => $label]),
                        );
                    }
                }
            }
        });
    }

    private function recurrenceEnabled(): bool
    {
        return filter_var($this->input('recurrence_enabled'), FILTER_VALIDATE_BOOL);
    }

    private function validateRecurrence(Validator $validator): void
    {
        $frequency = $this->input('recurrence_frequency');
        $endType = $this->input('recurrence_end_type');

        if (blank($frequency)) {
            $validator->errors()->add('recurrence_frequency', __('Bitte wählen Sie eine Wiederholung aus.'));
        }

        if ($frequency === RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS && blank($this->input('recurrence_interval_days'))) {
            $validator->errors()->add('recurrence_interval_days', __('Bitte geben Sie das Tagesintervall an.'));
        }

        if (blank($endType)) {
            $validator->errors()->add('recurrence_end_type', __('Bitte wählen Sie aus, wann die Wiederholung endet.'));

            return;
        }

        if ($endType === RecurringVisitSeries::END_DATE && blank($this->input('recurrence_end_date'))) {
            $validator->errors()->add('recurrence_end_date', __('Bitte geben Sie das Enddatum der Serie an.'));
        }

        if ($endType === RecurringVisitSeries::END_DATE && filled($this->input('recurrence_end_date')) && filled($this->input('scheduled_from'))) {
            try {
                $endDate = Carbon::parse($this->input('recurrence_end_date'))->startOfDay();
                $startDate = Carbon::parse($this->input('scheduled_from'))->startOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($endDate->lt($startDate)) {
                $validator->errors()->add('recurrence_end_date', __('Das Enddatum der Serie darf nicht vor dem Starttermin liegen.'));
            }
        }

        if ($endType === RecurringVisitSeries::END_COUNT && blank($this->input('recurrence_occurrence_count'))) {
            $validator->errors()->add('recurrence_occurrence_count', __('Bitte geben Sie die Anzahl der Termine insgesamt an.'));
        }
    }

    private function validateSelectableUsers(Validator $validator): void
    {
        $user = $this->user();

        if (! $user instanceof User) {
            $validator->errors()->add('host_user_id', __('Bitte melden Sie sich erneut an.'));

            return;
        }

        $userIds = collect([$this->input('host_user_id'), $this->input('substitute_user_id')])
            ->filter(fn ($userId) => filled($userId))
            ->map(fn ($userId) => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $inactiveUserIds = User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', false)
            ->pluck('id')
            ->map(fn ($userId) => (int) $userId);

        if ($inactiveUserIds->contains((int) $this->input('host_user_id'))) {
            $validator->errors()->add('host_user_id', __('Inaktive Benutzer können nicht als Host ausgewählt werden.'));
        }

        if ($inactiveUserIds->contains((int) $this->input('substitute_user_id'))) {
            $validator->errors()->add('substitute_user_id', __('Inaktive Benutzer können nicht als Vertretung ausgewählt werden.'));
        }

        $welcomeMonitorIds = User::query()
            ->whereIn('id', $userIds)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor']))
            ->pluck('id')
            ->map(fn ($userId) => (int) $userId);

        if ($welcomeMonitorIds->contains((int) $this->input('host_user_id'))) {
            $validator->errors()->add('host_user_id', __('Der WelcomeMonitor kann nicht als Host ausgewählt werden.'));
        }

        if ($welcomeMonitorIds->contains((int) $this->input('substitute_user_id'))) {
            $validator->errors()->add('substitute_user_id', __('Der WelcomeMonitor kann nicht als Vertretung ausgewählt werden.'));
        }

        $hostId = (int) $this->input('host_user_id');
        $siteId = $this->selectedSiteId();

        $this->validateSiteSelection($validator, $user, $siteId);

        $host = User::query()->with('sites:id')->find($hostId);

        if ($host && ! $host->canAccessSite($siteId)) {
            $validator->errors()->add('host_user_id', __('Der Host ist dem ausgewählten Standort nicht zugeordnet.'));
        }

        if ($this->canSelectAnyHost($user)) {
            $this->validateSubstituteUserSelection($validator, $user, $siteId);

            return;
        }

        $this->validateSubstituteUserSelection($validator, $user, $siteId);

        if ($this->canSelectSiteHost($user)) {
            return;
        }

        if ($this->canSelectDepartmentHost($user)) {
            $hostIsInDepartment = User::query()
                ->whereKey($hostId)
                ->where('department_id', $user->department_id)
                ->exists();

            if (! $hostIsInDepartment) {
                $validator->errors()->add('host_user_id', __('Sie können nur Hosts aus Ihrer Abteilung auswählen.'));
            }

            return;
        }

        if ($hostId !== $user->id) {
            $validator->errors()->add('host_user_id', __('Sie können Besuche nur für sich selbst anlegen oder bearbeiten.'));
        }
    }

    private function validateSubstituteUserSelection(Validator $validator, User $user, int $siteId): void
    {
        if (blank($this->input('substitute_user_id'))) {
            return;
        }

        $substituteUserId = (int) $this->input('substitute_user_id');
        $substituteUser = User::query()->with('sites:id')->find($substituteUserId);

        if ($substituteUser && ! $substituteUser->canAccessSite($siteId)) {
            $validator->errors()->add('substitute_user_id', __('Die Vertretung ist dem ausgewählten Standort nicht zugeordnet.'));
        }

        if ($this->canSelectAnyHost($user)) {
            return;
        }

        if ($this->canSelectSiteHost($user)) {
            return;
        }

        if ($this->canSelectDepartmentHost($user)) {
            $substituteUserIsInDepartment = User::query()
                ->whereKey($substituteUserId)
                ->where('department_id', $user->department_id)
                ->exists();

            if (! $substituteUserIsInDepartment) {
                $validator->errors()->add('substitute_user_id', __('Sie können nur Vertretungen aus Ihrer Abteilung auswählen.'));
            }

            return;
        }

        $validator->errors()->add('substitute_user_id', __('Sie können keine freie Vertretung auswählen.'));
    }

    private function selectedSiteId(): int
    {
        return (int) $this->input('site_id');
    }

    private function validateSiteSelection(Validator $validator, User $user, int $siteId): void
    {
        if ($siteId <= 0) {
            return;
        }

        if ($this->siteMustBeActive($siteId) && ! Site::query()->active()->whereKey($siteId)->exists()) {
            $validator->errors()->add('site_id', __('Der ausgewählte Standort ist nicht aktiv.'));

            return;
        }

        if (! $this->canSelectAnyHost($user) && ! $user->canAccessSite($siteId)) {
            $validator->errors()->add('site_id', __('Sie können nur Standorte auswählen, denen Sie zugeordnet sind.'));
        }
    }

    private function siteMustBeActive(int $siteId): bool
    {
        $visit = $this->route('visit');

        return ! $visit instanceof Visit || (int) $visit->site_id !== $siteId;
    }

    private function validateStatusSelection(Validator $validator): void
    {
        $user = $this->user();

        if ($user instanceof User && ($user->can('ViewAny:Visit') || $user->can('EditAny:Visit') || $user->can('ViewSite:Visit') || $user->can('EditSite:Visit'))) {
            return;
        }

        if (! in_array($this->input('status'), [VisitStatusEnum::Planned->value, VisitStatusEnum::Draft->value], true)) {
            $validator->errors()->add('status', __('Dieser Status darf nur über die vorgesehenen Aktionen gesetzt werden.'));
        }
    }

    private function validateParticipantVisibility(Validator $validator): void
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return;
        }

        foreach ($this->input('participants', []) as $index => $participant) {
            if (! is_array($participant) || blank($participant['visitor_id'] ?? null)) {
                continue;
            }

            $isVisible = Visitor::query()
                ->visibleTo($user)
                ->whereKey((int) $participant['visitor_id'])
                ->exists();

            if (! $isVisible) {
                $validator->errors()->add("participants.{$index}.visitor_id", __('Dieser Besucher kann für Ihren Besuch nicht verwendet werden.'));
            }
        }
    }

    private function canSelectAnyHost(User $user): bool
    {
        return $user->can('ViewAny:Visit')
            || $user->can('EditAny:Visit')
            || $user->can('DeleteAny:Visit')
            || $user->can('CreateForAny:Visit');
    }

    private function canSelectSiteHost(User $user): bool
    {
        return $user->assignedSiteIds()->isNotEmpty()
            && ($user->can('ViewSite:Visit')
                || $user->can('EditSite:Visit')
                || $user->can('DeleteSite:Visit')
                || $user->can('CreateForSite:Visit'));
    }

    private function canSelectDepartmentHost(User $user): bool
    {
        return $user->department_id !== null
            && ($user->can('ViewDepartment:Visit')
                || $user->can('EditDepartment:Visit')
                || $user->can('DeleteDepartment:Visit')
                || $user->can('CreateForDepartment:Visit'));
    }

    private function validateParticipantContactRequirement(Validator $validator): void
    {
        $requirement = VisitorContactRequirement::current();

        if ($requirement === VisitorContactRequirement::OPTIONAL) {
            return;
        }

        foreach ($this->input('participants', []) as $index => $participant) {
            if (! is_array($participant)) {
                continue;
            }

            [$email, $phone] = $this->participantContactValues($participant);

            if (VisitorContactRequirement::requiresEmail($requirement) && blank($email)) {
                $validator->errors()->add("participants.{$index}.email", __('Bitte geben Sie eine E-Mail-Adresse an.'));
            }

            if (VisitorContactRequirement::requiresPhone($requirement) && blank($phone)) {
                $validator->errors()->add("participants.{$index}.phone", __('Bitte geben Sie eine Telefonnummer an.'));
            }

            if (VisitorContactRequirement::requiresOne($requirement) && blank($email) && blank($phone)) {
                $validator->errors()->add("participants.{$index}.email", __('Bitte geben Sie eine E-Mail-Adresse oder Telefonnummer an.'));
            }
        }
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function participantContactValues(array $participant): array
    {
        $email = $this->normalizeInput($participant['email'] ?? null);
        $phone = $this->normalizeInput($participant['phone'] ?? null);

        // Existing participants may provide contact data through the stored visitor record.
        if (filled($participant['visitor_id'] ?? null)) {
            $user = $this->user();
            $visitor = $user instanceof User
                ? Visitor::query()
                    ->visibleTo($user)
                    ->select('id', 'email', 'phone')
                    ->find((int) $participant['visitor_id'])
                : null;

            $email ??= $visitor?->email;
            $phone ??= $visitor?->phone;
        }

        return [$email, $phone];
    }

    private function normalizeInput(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, string>
     */
    private function requiredInlineParticipantFields(): array
    {
        return [
            'first_name' => __('Vorname der teilnehmenden Person'),
            'name' => __('Nachname der teilnehmenden Person'),
        ];
    }
}
