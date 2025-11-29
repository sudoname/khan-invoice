<?php

namespace App\Filament\App\Resources\BusinessProfileResource\Pages;

use App\Filament\App\Resources\BusinessProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessProfile extends EditRecord
{
    protected static string $resource = BusinessProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
