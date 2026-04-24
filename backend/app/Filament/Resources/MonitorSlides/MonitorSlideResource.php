<?php

namespace App\Filament\Resources\MonitorSlides;

use App\Filament\Resources\MonitorSlides\Pages\CreateMonitorSlide;
use App\Filament\Resources\MonitorSlides\Pages\EditMonitorSlide;
use App\Filament\Resources\MonitorSlides\Pages\ListMonitorSlides;
use App\Filament\Resources\MonitorSlides\Pages\ViewMonitorSlide;
use App\Filament\Resources\MonitorSlides\Schemas\MonitorSlideForm;
use App\Filament\Resources\MonitorSlides\Schemas\MonitorSlideInfolist;
use App\Filament\Resources\MonitorSlides\Tables\MonitorSlidesTable;
use App\Models\MonitorSlide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MonitorSlideResource extends Resource
{
    protected static ?string $model = MonitorSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MonitorSlide';

    public static function form(Schema $schema): Schema
    {
        return MonitorSlideForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MonitorSlideInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonitorSlidesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitorSlides::route('/'),
            'create' => CreateMonitorSlide::route('/create'),
            'view' => ViewMonitorSlide::route('/{record}'),
            'edit' => EditMonitorSlide::route('/{record}/edit'),
        ];
    }
}
