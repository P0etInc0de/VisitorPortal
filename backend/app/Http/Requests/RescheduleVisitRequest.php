<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_from' => ['required', 'date'],
            'scheduled_until' => ['nullable', 'date', 'after:scheduled_from'],
        ];
    }
}
