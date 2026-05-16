<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Models\Site;
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
                    ->relationship('site', 'name')
                    ->default(fn () => Site::default()->id)
                    ->required()
                    ->preload(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, callable $get): Unique => $rule->where('site_id', $get('site_id'))),
                TextInput::make('location')
                    ->label(__('Standort')),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->required(),
            ]);
    }
}
