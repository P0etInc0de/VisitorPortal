<?php

namespace App\Rules;

use App\Support\RasterImageUpload;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class SafeRasterImage implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail(__('Bitte laden Sie eine gültige Bilddatei hoch.'));

            return;
        }

        try {
            RasterImageUpload::inspect($value);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
