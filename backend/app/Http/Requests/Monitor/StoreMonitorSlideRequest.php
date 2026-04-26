<?php

namespace App\Http\Requests\Monitor;

use App\Models\MonitorSlide;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonitorSlideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');

        return $this->user()->can('create', MonitorSlide::class) && $this->user()->can('update', $monitor);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSlide = MonitorSlide::max('slide_number') + 1;

        return [
            'visitors' => ['nullable', 'json'],
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
            'background_source' => 'required|in:inherit,preset-1,preset-2,preset-3,upload',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:20000',
            'deleteImage' => 'boolean',
        ];
    }
}
