<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Filament\Resources\Kiosks\Pages;

use App\Filament\Resources\Kiosks\KioskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKiosk extends CreateRecord
{
    protected static string $resource = KioskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
