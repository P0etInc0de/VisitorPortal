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
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('title'),
                Select::make('gender')
                    ->preload()
                    ->options(GenderEnum::class)
                    ->required()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('company'),
                Select::make('status')
                    ->options(VisitorStatusEnum::class)
                    ->default(VisitorStatusEnum::Planned)
                    ->preload(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
