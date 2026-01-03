<?php

namespace App\Filament\App\Resources\MerchantAccountResource\Pages;

use App\Filament\App\Resources\MerchantAccountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMerchantAccount extends CreateRecord
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Bank account added')
            ->body('Your bank account has been added and is pending verification.');
    }
}
