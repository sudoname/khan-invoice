<?php

namespace App\Filament\Admin\Resources\PublicInvoiceResource\Pages;

use App\Filament\Admin\Resources\PublicInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPublicInvoice extends EditRecord
{
    protected static string $resource = PublicInvoiceResource::class;

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
