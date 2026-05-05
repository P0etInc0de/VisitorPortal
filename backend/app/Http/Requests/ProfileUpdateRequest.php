<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
	public function authorize()
	{
		return $this->user()->can('editAny', User::class) || $this->user()->can('editDepartment', $this->user()) || $this->user()->can('editOwn', $this->user());
	}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'locale' => ['sometimes', 'string', Rule::in(UserPreferences::supportedLocales())],
            'theme_preference' => ['sometimes', 'string', Rule::in(UserPreferences::supportedThemes())],
        ];
    }
}
