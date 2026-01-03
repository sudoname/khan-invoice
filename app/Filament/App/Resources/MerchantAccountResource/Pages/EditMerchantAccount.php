<?php

namespace App\Filament\App\Resources\MerchantAccountResource\Pages;

use App\Filament\App\Resources\MerchantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMerchantAccount extends EditRecord
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
