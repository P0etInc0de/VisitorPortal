<?php

namespace App\Http\Requests;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use App\Policies\VisitPolicy;
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
        $visit = $this->route('visit');
        $assignableUserIds = app(VisitPolicy::class)->assignableUserIds($this->user(), $visit);

        return [
            'title' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'integer', 'exists:users,id', Rule::in($assignableUserIds)],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id', Rule::in($assignableUserIds)],
            'scheduled_from' => ['required', 'date', 'after_or_equal:1970-01-01'],
            'scheduled_until' => ['required', 'date', 'after:scheduled_from', 'after_or_equal:1970-01-01'],
            'status' => ['required', 'string', Rule::in(VisitStatusEnum::values())],
            'notes' => ['nullable', 'string'],
            'participants' => ['required', 'array', 'min:1'],
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
        ];
    }
}
