<?php

namespace App\Filament\Resources\MonitorSlides\Pages;

use App\Filament\Resources\MonitorSlides\MonitorSlideResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitorSlide extends ViewRecord
{
    protected static string $resource = MonitorSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
