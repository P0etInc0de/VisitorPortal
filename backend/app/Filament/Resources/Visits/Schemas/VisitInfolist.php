<?php

namespace App\Filament\Resources\Visits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VisitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('creator.fullName')
                    ->label('Creator'),
                TextEntry::make('responsibleUser.fullName')
                    ->label('Responsible user'),
                TextEntry::make('scheduled_from')
                    ->dateTime(),
                TextEntry::make('scheduled_until')
                    ->dateTime(),
                TextEntry::make('status'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('canceled_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('canceledBy.fullName')
                    ->label('Canceled by')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
