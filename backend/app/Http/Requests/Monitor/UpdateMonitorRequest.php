<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transition_time_milliseconds' => 'required|integer|between:1000,50000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20000',
            'deleteImage' => 'boolean',
        ];
    }
}
