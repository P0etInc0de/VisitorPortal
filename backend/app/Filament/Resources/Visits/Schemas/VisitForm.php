<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\VisitStatusEnum;
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
                    ->relationship('creator', 'name')
                    ->required(),
                Select::make('responsible_user_id')
                    ->relationship('responsibleUser', 'name')
                    ->required(),
                DateTimePicker::make('scheduled_from')
                    ->required(),
                DateTimePicker::make('scheduled_until')
                    ->required(),
                Select::make('status')
                    ->options(VisitStatusEnum::options())
                    ->required()
                    ->default(VisitStatusEnum::Planned->value),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('canceled_at'),
                Select::make('canceled_by_user_id')
                    ->relationship('canceledBy', 'name'),
                Select::make('visitors')
                    ->multiple()
                    ->relationship(name: 'visitors')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->email})")
                    ->searchable(['first_name', 'last_name'])
                    ->preload()
                    ->label('Select Visitors'),

            ]);
    }
}
