<?php

namespace App\Filament\App\Resources\MerchantAccountResource\Pages;

use App\Filament\App\Resources\MerchantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchantAccount extends ViewRecord
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }
}
