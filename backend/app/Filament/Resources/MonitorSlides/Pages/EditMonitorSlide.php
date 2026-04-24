<?php

namespace App\Filament\Resources\MonitorSlides\Pages;

use App\Filament\Resources\MonitorSlides\MonitorSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMonitorSlide extends EditRecord
{
    protected static string $resource = MonitorSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
