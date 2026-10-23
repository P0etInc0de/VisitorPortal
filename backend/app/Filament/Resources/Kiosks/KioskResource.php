<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Kiosks;

use App\Filament\Resources\Kiosks\Pages\CreateKiosk;
use App\Filament\Resources\Kiosks\Pages\EditKiosk;
use App\Filament\Resources\Kiosks\Pages\ListKiosks;
use App\Filament\Resources\Kiosks\Schemas\KioskForm;
use App\Filament\Resources\Kiosks\Tables\KiosksTable;
use App\Models\Kiosk;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KioskResource extends Resource
{
    protected static ?string $model = Kiosk::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-computer-desktop';

    public static function getNavigationLabel(): string
    {
        return __('Kiosk-Terminals');
    }

    public static function getModelLabel(): string
    {
        return __('Kiosk-Terminal');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Kiosk-Terminals');
    }

    public static function form(Schema $schema): Schema
    {
        return KioskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KiosksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user instanceof User && ! $user->can('ViewAny:Kiosk') && ! $user->can('ManageAny:Kiosk')) {
            $query->whereIn('site_id', $user->assignedSiteIds()->all());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKiosks::route('/'),
            'create' => CreateKiosk::route('/create'),
            'edit' => EditKiosk::route('/{record}/edit'),
        ];
    }
}
