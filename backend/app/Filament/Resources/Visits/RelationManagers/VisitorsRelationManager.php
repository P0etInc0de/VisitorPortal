<?php

namespace App\Filament\Resources\Visits\RelationManagers;

use App\Filament\Resources\Visitors\VisitorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class VisitorsRelationManager extends RelationManager
{
    protected static string $relationship = 'Visitors';

    protected static ?string $relatedResource = VisitorResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
