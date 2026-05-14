<?php

namespace App\Http\Requests;

use App\Models\Visit;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('visit');

        return $visit instanceof Visit && $this->user()?->can('update', $visit);
    }

    public function rules(): array
    {
        return [
            'scheduled_from' => ['required', 'date'],
            'scheduled_until' => ['nullable', 'date', 'after:scheduled_from'],
        ];
    }
}
