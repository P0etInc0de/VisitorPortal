<?php

namespace App\Filament\Resources\Monitors\Pages;

use App\Filament\Resources\Monitors\MonitorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMonitor extends EditRecord
{
    protected static string $resource = MonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
