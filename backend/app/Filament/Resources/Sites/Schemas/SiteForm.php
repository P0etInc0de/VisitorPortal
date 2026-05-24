<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('Slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('address')
                    ->label(__('Adresse'))
                    ->maxLength(255),
                TextInput::make('timezone')
                    ->label(__('Zeitzone'))
                    ->required()
                    ->default('Europe/Berlin')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->default(true),
            ]);
    }
}
