<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VisitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('host.fullName')
                    ->label(__('Host')),
                TextEntry::make('substituteUser.fullName')
                    ->label(__('Vertretung'))
                    ->placeholder('-'),
                TextEntry::make('createdBy.fullName')
                    ->label(__('Erstellt von'))
                    ->placeholder('-'),
                TextEntry::make('title')
                    ->label(__('Titel')),
                TextEntry::make('scheduled_from')
                    ->label(__('Beginn'))
                    ->dateTime(),
                TextEntry::make('scheduled_until')
                    ->label(__('Ende'))
                    ->dateTime(),
                TextEntry::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => __('Geplant'),
                        'draft' => __('Entwurf'),
                        'completed' => __('Abgeschlossen'),
                        'canceled' => __('Abgesagt'),
                        default => $state,
                    }),
                TextEntry::make('notes')
                    ->label(__('Notizen'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('canceled_at')
                    ->label(__('Abgesagt am'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('canceledBy.fullName')
                    ->label(__('Abgesagt von'))
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
