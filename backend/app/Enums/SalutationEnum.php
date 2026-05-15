<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SalutationEnum: string implements HasLabel
{
    case Mr = 'mr';
    case Ms = 'ms';
    case NotSpecified = 'not_specified';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mr => __('Herr'),
            self::Ms => __('Frau'),
            self::NotSpecified => __('Nicht angegeben'),
        };
    }
}
