<?php

namespace App\Filament\Resources\Users\Schemas;

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
                TextInput::make('name')
                    ->required(),
                Select::make('gender')
                    ->options(['male'=>'male', 'female'=>'female', 'not specified'=>'not specified'])
                    ->required()
                    ->default('not specified'),
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
