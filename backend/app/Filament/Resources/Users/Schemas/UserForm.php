<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\GenderEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->relationship('department', 'name'),
                TextInput::make('first_name')
                    ->label('Vorname')
                    ->required(),
                TextInput::make('name')
                    ->label('Nachname')
                    ->required(),
                TextInput::make('title')
                    ->label('Titel'),
                Select::make('gender')
                    ->options(GenderEnum::class)
                    ->required()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
