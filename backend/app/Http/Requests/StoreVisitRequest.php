<?php

namespace App\Http\Requests;

use App\Enums\VisitStatusEnum;
use App\Models\RecurringVisitSeries;
use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Visit::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_from' => ['required', 'date', 'after_or_equal:1970-01-01'],
            'scheduled_until' => ['required', 'date', 'after:scheduled_from', 'after_or_equal:1970-01-01'],
            'status' => ['required', 'string', Rule::in(VisitStatusEnum::values())],
            'notes' => ['nullable', 'string'],
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
            'participants.*.phone' => ['nullable', 'string', 'max:255'],
            'participants.*.company' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_from.after_or_equal' => __('Das Startdatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after_or_equal' => __('Das Enddatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after' => __('Das Ende muss nach dem Beginn liegen.'),
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

    /**
     * @return array<string, string>
     */
    private function requiredInlineParticipantFields(): array
    {
        return [
            'first_name' => __('Vorname der teilnehmenden Person'),
            'name' => __('Nachname der teilnehmenden Person'),
            'email' => __('E-Mail der teilnehmenden Person'),
            'phone' => __('Telefon der teilnehmenden Person'),
            'company' => __('Firma der teilnehmenden Person'),
        ];
    }
}
