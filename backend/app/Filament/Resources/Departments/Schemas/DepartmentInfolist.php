<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DepartmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('site.name')
                    ->label(__('Standort')),
                TextEntry::make('name')
                    ->label(__('Name')),
                TextEntry::make('location')
                    ->label(__('Standort'))
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label(__('Aktiv'))
                    ->boolean(),
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
