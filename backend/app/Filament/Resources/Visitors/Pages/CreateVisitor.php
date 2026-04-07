<?php

namespace App\Filament\Resources\Visitors\Pages;

use App\Filament\Resources\Visitors\VisitorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitor extends CreateRecord
{
    protected static string $resource = VisitorResource::class;
}
