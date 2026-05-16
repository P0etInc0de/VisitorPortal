<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\GenderEnum;
use App\Models\Department;
use App\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->label(__('Primärer Standort'))
                    ->relationship('site', 'name')
                    ->default(fn () => Site::default()->id)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, callable $get, mixed $state): void {
                        $set('department_id', null);

                        if (blank($state)) {
                            return;
                        }

                        $set('sites', collect($get('sites') ?? [])
                            ->push((int) $state)
                            ->map(fn ($siteId): int => (int) $siteId)
                            ->unique()
                            ->values()
                            ->all());
                    })
                    ->preload(),
                Select::make('sites')
                    ->label(__('Zugeordnete Standorte'))
                    ->relationship('sites', 'name')
                    ->multiple()
                    ->default(fn (callable $get): array => [(int) ($get('site_id') ?: Site::default()->id)])
                    ->preload()
                    ->searchable()
                    ->helperText(__('Der primäre Standort wird automatisch zugeordnet.')),
                Select::make('department_id')
                    ->label(__('Abteilung'))
                    ->options(fn (callable $get): array => Department::query()
                        ->where('site_id', $get('site_id') ?: Site::default()->id)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
                TextInput::make('first_name')
                    ->label(__('Vorname'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('Nachname'))
                    ->required(),
                Select::make('roles')
                    ->label(__('Rollen'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('title')
                    ->label(__('Titel')),
                Select::make('gender')
                    ->label(__('Geschlecht'))
                    ->options(GenderEnum::class)
                    ->required()
                    ->preload()
                    ->default(GenderEnum::Not_Specified),
                TextInput::make('email')
                    ->label(__('E-Mail'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label(__('Passwort'))
                    ->password()
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('Aktiv'))
                    ->default(true),
            ]);
    }
}
