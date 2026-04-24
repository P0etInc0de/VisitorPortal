<?php

namespace App\Filament\Resources\MonitorSlides\Pages;

use App\Filament\Resources\MonitorSlides\MonitorSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMonitorSlides extends ListRecords
{
    protected static string $resource = MonitorSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
