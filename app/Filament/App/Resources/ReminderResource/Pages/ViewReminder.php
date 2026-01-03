<?php

namespace App\Filament\App\Resources\ReminderResource\Pages;

use App\Filament\App\Resources\ReminderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReminder extends ViewRecord
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === 'SCHEDULED'),
            Actions\DeleteAction::make(),
        ];
    }
}
