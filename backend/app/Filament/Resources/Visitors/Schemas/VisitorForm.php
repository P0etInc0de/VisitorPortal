<?php

namespace App\Filament\Resources\Visitors\Schemas;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VisitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('company'),
                Select::make('status')
                    ->options(VisitorStatusEnum::class)
                    ->default(VisitorStatusEnum::Planned),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
