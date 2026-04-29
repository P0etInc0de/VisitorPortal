<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\GenderEnum;
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
                    ->relationship('department', 'name')
                    ->preload(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('title'),
                Select::make('gender')
                    ->options(GenderEnum::class)
                    ->required()
                    ->preload()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
