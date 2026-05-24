<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('department.name')
                    ->label(__('Abteilung'))
                    ->placeholder('-'),
                TextEntry::make('first_name')
                    ->label(__('Vorname')),
                TextEntry::make('name')
                    ->label(__('Nachname')),
                TextEntry::make('title')
                    ->label(__('Titel')),
                TextEntry::make('gender')
                    ->label(__('Geschlecht')),
                TextEntry::make('roles.name')
                    ->label(__('Rollen')),
                TextEntry::make('email')
                    ->label(__('E-Mail')),
                TextEntry::make('is_active')
                    ->label(__('Aktiv'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('Ja') : __('Nein')),
                TextEntry::make('deactivated_at')
                    ->label(__('Deaktiviert am'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('email_verified_at')
                    ->label(__('E-Mail verifiziert am'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('Erstellt am'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
