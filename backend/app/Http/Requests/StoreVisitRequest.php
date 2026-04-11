<?php

namespace App\Http\Requests;

use App\Enums\VisitStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'participants.*.first_name' => ['nullable', 'string', 'max:255'],
            'participants.*.last_name' => ['nullable', 'string', 'max:255'],
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
        ];
    }
}
