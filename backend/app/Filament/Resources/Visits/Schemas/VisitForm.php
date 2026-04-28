<?php

namespace App\Filament\Resources\Visits\Schemas;

use App\Enums\VisitStatusEnum;
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
                Select::make('user_id')
                    ->relationship(name: 'creator', titleAttribute: 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->default(auth()->id())
                    ->disabled()
                    ->dehydrated(), // ensure it's still saved
                Select::make('responsible_user_id')
                    ->relationship(name: 'responsibleUser', titleAttribute: 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->fullName)
                    ->searchable(['first_name', 'name'])
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('scheduled_from')
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('scheduled_until')
                    ->seconds(false)
                    ->required(),
                Select::make('status')
                    ->options(VisitStatusEnum::options())
                    ->required()
                    ->preload()
                    ->default(VisitStatusEnum::Planned->value),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Select::make('visitors')
                    ->multiple()
                    ->relationship(name: 'visitors')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->name} ({$record->email})")
                    ->searchable(['first_name', 'name'])
                    ->preload(),

            ]);
    }
}
