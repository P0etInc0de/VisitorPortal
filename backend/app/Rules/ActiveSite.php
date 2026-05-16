<?php

namespace App\Rules;

use App\Models\Site;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveSite implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Site::query()->active()->whereKey((int) $value)->exists()) {
            $fail(__('Der ausgewählte Standort ist nicht aktiv.'));
        }
    }
}
