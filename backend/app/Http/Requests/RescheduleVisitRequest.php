<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editAny', Visit::class) || $this->user()->can('editDepartment', Visit::class) || $this->user()->can('editOwn', Visit::class);
    }

    public function rules(): array
    {
        return [
            'scheduled_from' => ['required', 'date'],
            'scheduled_until' => ['nullable', 'date', 'after:scheduled_from'],
        ];
    }
}
