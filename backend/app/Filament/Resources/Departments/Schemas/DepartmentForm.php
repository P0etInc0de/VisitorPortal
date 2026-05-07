<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('location')
                    ->label(__('Standort')),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->required(),
            ]);
    }
}
