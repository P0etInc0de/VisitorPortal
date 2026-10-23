<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Kiosks\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KioskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Select::make('site_id')
                    ->label(__('Standort'))
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('walk_in_host_user_id')
                    ->label(__('Host für Walk-ins'))
                    ->relationship('walkInHost', 'name')
                    ->getOptionLabelFromRecordUsing(
                        fn (User $record): string => trim(($record->first_name ?? '').' '.($record->name ?? ''))
                    )
                    ->searchable()
                    ->preload()
                    ->helperText(__('Erforderlich, damit Gäste ohne Termin sich am Kiosk selbst registrieren können.')),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->default(true),
                Toggle::make('allow_walk_in')
                    ->label(__('Walk-in Self-Registration erlauben'))
                    ->default(true),
                Toggle::make('allow_check_out')
                    ->label(__('Self-Check-out erlauben'))
                    ->default(true),
                Toggle::make('require_signature')
                    ->label(__('Unterschrift erforderlich'))
                    ->default(true),
            ]);
    }
}
