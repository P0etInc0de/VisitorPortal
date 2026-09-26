<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Kiosks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KiosksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label(__('Standort'))
                    ->sortable(),
                TextColumn::make('public_url')
                    ->label(__('Kiosk-URL'))
                    ->copyable()
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('Aktiv'))
                    ->boolean(),
                IconColumn::make('allow_walk_in')
                    ->label(__('Walk-in'))
                    ->boolean(),
                IconColumn::make('allow_check_out')
                    ->label(__('Check-out'))
                    ->boolean(),
                IconColumn::make('require_signature')
                    ->label(__('Unterschrift'))
                    ->boolean(),
                TextColumn::make('last_seen_at')
                    ->label(__('Zuletzt gesehen'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('–')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
