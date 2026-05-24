<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Visitors\Schemas;

use App\Enums\SalutationEnum;
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
                Select::make('salutation')
                    ->label(__('Anrede'))
                    ->preload()
                    ->options(SalutationEnum::class)
                    ->default(SalutationEnum::NotSpecified),
                TextInput::make('email')
                    ->label(__('E-Mail'))
                    ->email()
                    ->maxLength(255)
                    ->required(VisitorContactRequirement::requiresEmail())
                    ->rules(VisitorContactRequirement::requiresOne() ? ['required_without:phone'] : []),
                TextInput::make('phone')
                    ->label(__('Telefon'))
                    ->tel()
                    ->maxLength(50)
                    ->required(VisitorContactRequirement::requiresPhone())
                    ->rules(VisitorContactRequirement::requiresOne() ? ['required_without:email'] : []),
                TextInput::make('company')
                    ->label(__('Firma')),
                Textarea::make('notes')
                    ->label(__('Notizen'))
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
