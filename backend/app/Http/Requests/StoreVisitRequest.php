<?php

namespace App\Http\Requests;

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
            'scheduled_from' => ['required', 'date'],
            'scheduled_until' => ['nullable', 'date', 'after_or_equal:scheduled_from'],
            'status' => ['required', Rule::in(['draft', 'planned', 'completed', 'canceled'])],
            'notes' => ['nullable', 'string'],
            'participants' => ['nullable', 'array'],
            'participants.*.visitor_id' => ['nullable', 'integer', 'exists:visitors,id'],
            'participants.*.first_name' => ['nullable', 'string', 'max:255'],
            'participants.*.last_name' => ['nullable', 'string', 'max:255'],
            'participants.*.email' => ['nullable', 'email', 'max:255'],
            'participants.*.phone' => ['nullable', 'string', 'max:255'],
            'participants.*.company' => ['nullable', 'string', 'max:255'],
            'participants.*.notes' => ['nullable', 'string'],
        ];
    }
}
