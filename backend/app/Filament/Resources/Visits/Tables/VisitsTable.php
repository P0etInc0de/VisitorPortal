<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Visits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VisitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('site.name')
                    ->label(__('Standort'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('Titel'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scheduled_from')
                    ->label(__('Beginn'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('scheduled_until')
                    ->label(__('Ende'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => __('Geplant'),
                        'draft' => __('Entwurf'),
                        'completed' => __('Abgeschlossen'),
                        'canceled' => __('Abgesagt'),
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('canceled_at')
                    ->label(__('Abgesagt am'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('canceledBy.fullName')
                    ->label(__('Abgesagt von'))
                    ->searchable(['first_name', 'name'])
                    ->sortable(['first_name', 'name']),
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
