<?php

namespace App\Filament\Resources\Visitors\Schemas;

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
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
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
