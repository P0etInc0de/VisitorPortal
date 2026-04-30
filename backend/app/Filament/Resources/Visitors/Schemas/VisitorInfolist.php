<?php

namespace App\Filament\Resources\Visitors\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class VisitorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name')
                    ->label(__('Vorname')),
                TextEntry::make('name')
                    ->label(__('Nachname')),
                TextEntry::make('title')
                    ->label(__('Titel')),
                TextEntry::make('gender')
                    ->label(__('Geschlecht')),
                TextEntry::make('email')
                    ->label(__('E-Mail')),
                TextEntry::make('phone')
                    ->label(__('Telefon')),
                TextEntry::make('company')
                    ->label(__('Firma'))
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => __('Geplant'),
                        'canceled' => __('Abgesagt'),
                        'checked_in' => __('Eingecheckt'),
                        'checked_out' => __('Ausgecheckt'),
                        default => $state,
                    }),
                TextEntry::make('notes')
                    ->label(__('Notizen'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label(__('Erstellt am'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('Aktualisiert am'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
