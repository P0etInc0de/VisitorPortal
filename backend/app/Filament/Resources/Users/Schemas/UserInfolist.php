<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),
                TextEntry::make('first_name')
                    ->label('Vorname'),
                TextEntry::make('name')
                    ->label('Nachname'),
                TextEntry::make('title')
                    ->label('Titel'),
                TextEntry::make('gender'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime()
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
