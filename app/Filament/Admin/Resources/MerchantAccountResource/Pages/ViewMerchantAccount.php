<?php

namespace App\Filament\Admin\Resources\MerchantAccountResource\Pages;

use App\Filament\Admin\Resources\MerchantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMerchantAccount extends ViewRecord
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
