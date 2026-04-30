<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department.name')
                    ->label(__('Abteilung'))
                    ->searchable(),
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
