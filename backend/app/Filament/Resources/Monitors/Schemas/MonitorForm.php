<?php

namespace App\Filament\Resources\Monitors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MonitorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->image(),
                TextInput::make('name')
                    ->required()
                    ->default('Willkommensmonitor Eingang'),
                TextInput::make('transition_time_milliseconds')
                    ->required()
                    ->numeric(),
                Toggle::make('auto_generation')
                    ->required(),
            ]);
    }
}
