<?php

namespace App\Filament\Resources\MonitorSlides\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MonitorSlideInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('monitor_id')
                    ->numeric(),
                ImageEntry::make('image_path')
                    ->placeholder('-'),
                TextEntry::make('slide_number')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('heading'),
                TextEntry::make('subheading')
                    ->placeholder('-'),
                IconEntry::make('show_logo')
                    ->boolean(),
                IconEntry::make('show_date')
                    ->boolean(),
                IconEntry::make('show_time')
                    ->boolean(),
            ]);
    }
}
