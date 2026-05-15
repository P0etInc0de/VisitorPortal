<?php

namespace App\Filament\Resources\Visitors\Schemas;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use App\Support\VisitorContactRequirement;
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
                    ->label(__('Vorname'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('Nachname'))
                    ->required(),
                TextInput::make('title')
                    ->label(__('Titel')),
                Select::make('gender')
                    ->label(__('Geschlecht'))
                    ->preload()
                    ->options(GenderEnum::class)
                    ->required()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->label(__('E-Mail'))
                    ->email()
                    ->maxLength(255)
                    ->required(VisitorContactRequirement::requiresEmail())
                    ->rules(VisitorContactRequirement::requiresOne() ? ['required_without:phone'] : [])
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label(__('Telefon'))
                    ->tel()
                    ->maxLength(50)
                    ->required(VisitorContactRequirement::requiresPhone())
                    ->rules(VisitorContactRequirement::requiresOne() ? ['required_without:email'] : [])
                    ->unique(ignoreRecord: true),
                TextInput::make('company')
                    ->label(__('Firma')),
                Select::make('status')
                    ->label(__('Status'))
                    ->options(VisitorStatusEnum::class)
                    ->default(VisitorStatusEnum::Planned)
                    ->preload(),
                Textarea::make('notes')
                    ->label(__('Notizen'))
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
