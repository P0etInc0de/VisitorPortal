<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorFallbackPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');

        return $this->user()->can('update', $monitor);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'heading' => 'required|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'show_logo' => 'boolean',
            'show_date' => 'boolean',
            'background_source' => 'required|in:inherit,preset-1,preset-2,preset-3,upload',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:20000',
            'deleteImage' => 'boolean',
        ];
    }
}
