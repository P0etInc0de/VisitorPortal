<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\VisitStatusEnum;
use App\Models\Site;
use App\Models\User;
use App\Rules\ActiveSite;
use App\Rules\UserCanAccessSite;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->label(__('Standort'))
                    ->options(fn (): array => Site::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn () => self::defaultActiveSiteId())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('host_user_id', null);
                        $set('substitute_user_id', null);
                    })
                    ->rules([new ActiveSite])
                    ->preload(),
                Select::make('host_user_id')
                    ->label(__('Host'))
                    ->options(fn (callable $get): array => self::userOptionsForSite((int) ($get('site_id') ?: self::defaultActiveSiteId())))
                    ->default(auth()->id())
                    ->dehydrated()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules(fn (callable $get): array => [
                        new UserCanAccessSite((int) ($get('site_id') ?: self::defaultActiveSiteId()), __('Der Host ist dem ausgewählten Standort nicht zugeordnet.')),
                    ]),
                Select::make('substitute_user_id')
                    ->label(__('Vertretung'))
                    ->options(fn (callable $get): array => self::userOptionsForSite((int) ($get('site_id') ?: self::defaultActiveSiteId())))
                    ->searchable()
                    ->preload()
                    ->different('host_user_id')
                    ->nullable()
                    ->rules(fn (callable $get): array => [
                        new UserCanAccessSite((int) ($get('site_id') ?: self::defaultActiveSiteId()), __('Die Vertretung ist dem ausgewählten Standort nicht zugeordnet.')),
                    ]),
                TextInput::make('title')
                    ->label(__('Titel'))
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('scheduled_from')
                    ->label(__('Beginn'))
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('scheduled_until')
                    ->label(__('Ende'))
                    ->seconds(false)
                    ->required()
                    ->after('scheduled_from'),
                Select::make('status')
                    ->label(__('Status'))
                    ->options(VisitStatusEnum::options())
                    ->disableOptionWhen(fn ($value, $record) => $value === VisitStatusEnum::Canceled->value &&
                        $record?->status !== VisitStatusEnum::Canceled->value
                    )
                    ->required()
                    ->preload()
                    ->default(VisitStatusEnum::Planned->value)
                    ->disabled(fn ($record) => $record?->status === VisitStatusEnum::Canceled->value),
                Toggle::make('is_confidential')
                    ->label(__('Vertraulich'))
                    ->default(false),
                Textarea::make('notes')
                    ->label(__('Notizen'))
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Select::make('visitors')
                    ->label(__('Besucher'))
                    ->multiple()
                    ->relationship(name: 'visitors')
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim(implode(' ', array_filter([
                        trim("{$record->first_name} {$record->name}"),
                        $record->email ? "({$record->email})" : null,
                    ]))))
                    ->searchable(['first_name', 'name'])
                    ->preload(),

            ]);
    }

    private static function defaultActiveSiteId(): int
    {
        return (int) (Site::query()->active()->value('id') ?: Site::default()->id);
    }

    /**
     * @return array<int, string>
     */
    private static function userOptionsForSite(int $siteId): array
    {
        return self::usersForSite($siteId)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->fullName])
            ->all();
    }

    private static function usersForSite(int $siteId)
    {
        return User::query()
            ->with('sites:id')
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor']))
            ->where(function ($query) use ($siteId): void {
                $query->where('site_id', $siteId)
                    ->orWhereHas('sites', fn ($query) => $query->whereKey($siteId));
            })
            ->orderBy('first_name')
            ->orderBy('name');
    }
}
