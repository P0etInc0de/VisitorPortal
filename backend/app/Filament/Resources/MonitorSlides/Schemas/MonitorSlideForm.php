<?php

namespace App\Filament\Resources\MonitorSlides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MonitorSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('monitor_id')
                    ->required()
                    ->numeric(),
                FileUpload::make('image_path')
                    ->image(),
                TextInput::make('slide_number')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('heading')
                    ->required(),
                TextInput::make('subheading'),
                Toggle::make('show_logo')
                    ->required(),
                Toggle::make('show_date')
                    ->required(),
                Toggle::make('show_time')
                    ->required(),
                TextInput::make('visitors'),
            ]);
    }
}
