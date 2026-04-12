<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship(name: 'creator', titleAttribute: 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->searchable(['first_name', 'name']),
                Select::make('responsible_user_id')
                    ->relationship(name: 'responsibleUser', titleAttribute: 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->searchable(['first_name', 'name']),
                DateTimePicker::make('scheduled_from')
                    ->required(),
                DateTimePicker::make('scheduled_until')
                    ->required(),
                Select::make('status')
                    ->options(VisitStatusEnum::class)
                    ->required()
                    ->default(VisitStatusEnum::Planned),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('canceled_at'),
                Select::make('canceledBy')
                    ->relationship(name: 'canceledBy', titleAttribute: 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->searchable(['first_name', 'name']),
                Select::make('visitors')
                    ->multiple()
                    ->relationship(name: 'visitors')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->name} ({$record->email})")
                    ->searchable(['first_name', 'name'])
                    ->preload()
                    ->label('Select Visitors'),

            ]);
    }
}
