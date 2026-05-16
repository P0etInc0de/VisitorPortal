<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\VisitStatusEnum;
use App\Models\Site;
use App\Models\Visit;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VisitForm
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
                Select::make('user_id')
                    ->label(__('Host'))
                    ->relationship(
                        name: 'creator',
                        titleAttribute: 'first_name',
                        modifyQueryUsing: fn ($query) => $query->whereDoesntHave(
                            'roles',
                            fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor'])
                        ),
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->default(auth()->id())
                    ->disabled()
                    ->dehydrated(), // ensure it's still saved
                Select::make('responsible_user_id')
                    ->label(__('Vertretung'))
                    ->relationship(
                        name: 'responsibleUser',
                        titleAttribute: 'first_name',
                        modifyQueryUsing: fn ($query) => $query->whereDoesntHave(
                            'roles',
                            fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor'])
                        ),
                    )
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->searchable(['first_name', 'name'])
                    ->preload()
                    ->different('user_id')
                    ->required(),
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
                Select::make('monitor_display_mode')
                    ->label(__('Monitor-Anzeige'))
                    ->options(Visit::monitorDisplayModeOptions())
                    ->required()
                    ->default(Visit::MONITOR_DISPLAY_COMPANY_ONLY)
                    ->preload(),
                \Filament\Forms\Components\Toggle::make('is_confidential')
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
}
