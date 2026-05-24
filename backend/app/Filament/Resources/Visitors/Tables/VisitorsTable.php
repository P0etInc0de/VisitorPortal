<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Visitors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label(__('Vorname'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('Nachname'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('Titel')),
                TextColumn::make('email')
                    ->label(__('E-Mail'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('Telefon'))
                    ->searchable(),
                TextColumn::make('company')
                    ->label(__('Firma'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Erstellt am'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
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
