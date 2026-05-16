<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\GenderEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('department_id')
                    ->label(__('Abteilung'))
                    ->relationship('department', 'name')
                    ->preload(),
                TextInput::make('first_name')
                    ->label(__('Vorname'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('Nachname'))
                    ->required(),
                Select::make('roles')
                    ->label(__('Rollen'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('title')
                    ->label(__('Titel')),
                Select::make('gender')
                    ->label(__('Geschlecht'))
                    ->options(GenderEnum::class)
                    ->required()
                    ->preload()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->label(__('E-Mail'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label(__('Passwort'))
                    ->password()
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->default(true),
            ]);
    }
}
