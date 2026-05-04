<?php

namespace App\Http\Requests;

use App\Enums\VisitStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.visitor_id' => ['nullable', 'integer', 'exists:visitors,id'],
            'participants.*.title' => ['nullable', 'string', 'max:255'],
            'participants.*.first_name' => ['required', 'string', 'max:255'],
            'participants.*.name' => ['required', 'string', 'max:255'],
            'participants.*.email' => ['required', 'email', 'max:255'],
            'participants.*.phone' => ['required', 'string', 'max:255'],
            'participants.*.company' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_from.after_or_equal' => __('Das Startdatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after_or_equal' => __('Das Enddatum muss innerhalb des technisch unterstützten Bereichs liegen.'),
            'scheduled_until.after' => __('Das Ende muss nach dem Beginn liegen.'),
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
        ];
    }
}
