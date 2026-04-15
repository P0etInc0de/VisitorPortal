<?php

namespace App\Http\Requests;

use App\Models\MonitorSlide;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorSlideRequest extends FormRequest
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
        $maxSlide = MonitorSlide::max('slide_number');

        return [
            'heading' => 'required',
            'slide_number' => [
                'required',
                'integer',
                'between:1,'.$maxSlide,
            ],
            'show_logo' => 'boolean',
            'show_date' => 'boolean',
            'show_time' => 'boolean',
            'subheading' => 'string|nullable',
        ];
    }
}
