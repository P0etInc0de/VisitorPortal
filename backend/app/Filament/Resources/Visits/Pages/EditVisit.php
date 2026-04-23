<?php

namespace App\Filament\Resources\Visits\Pages;

use App\Enums\VisitStatusEnum;
use App\Filament\Resources\Visits\VisitResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('cancel')
                ->label(__('Cancel Visit'))
                ->color('danger') // Makes the button red
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation() // Pops up a warning modal first!
                ->modalHeading(__('Cancel Visit'))
                ->modalDescription(__('Are you sure you want to cancel this visit?'))
                ->modalSubmitActionLabel(__('Yes, cancel it'))
                ->hidden(fn ($record) => $record->status === VisitStatusEnum::Canceled->value || $record->status === VisitStatusEnum::Completed->value) // Hides the button if already cancelled or finished
                ->action(function ($record) {
                    // Set the properties directly
                    $record->canceled_at = now();
                    $record->canceled_by_user_id = auth()->id();
                    $record->status = VisitStatusEnum::Canceled->value;

                    // Save the record to the database
                    $record->save();
                    $this->redirect('/admin/visits');
                }),
            DeleteAction::make(),
        ];
    }
}
