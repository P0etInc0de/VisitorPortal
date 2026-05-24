<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Site;
use App\Rules\ActiveSite;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->label(__('Standort'))
                    ->options(fn (): array => Site::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn () => Site::query()->active()->value('id') ?: Site::default()->id)
                    ->required()
                    ->rules([new ActiveSite])
                    ->preload(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get): Unique => $rule->where('site_id', $get('site_id'))),
                TextInput::make('location')
                    ->label(__('Gebäude/Raum')),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->required(),
            ]);
    }
}
