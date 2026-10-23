<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Kiosks\Pages;

use App\Filament\Resources\Kiosks\KioskResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKiosk extends EditRecord
{
    protected static string $resource = KioskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateToken')
                ->label(__('Kiosk-URL neu erzeugen'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('Der bisherige Link wird sofort ungültig. Das Terminal muss neu eingerichtet werden.'))
                ->action(function (): void {
                    $this->record->regenerateToken();
                    $this->refreshFormData(['token']);
                }),
            DeleteAction::make(),
        ];
    }
}
