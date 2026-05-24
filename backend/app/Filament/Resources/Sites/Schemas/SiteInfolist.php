<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Sites\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label(__('Name')),
                TextEntry::make('slug')->label(__('Slug')),
                TextEntry::make('address')->label(__('Adresse'))->placeholder('-'),
                TextEntry::make('timezone')->label(__('Zeitzone')),
                IconEntry::make('is_active')->label(__('Aktiv'))->boolean(),
            ]);
    }
}
