<?php

namespace App\Filament\Admin\Resources\InvoicePaymentResource\Pages;

use App\Filament\Admin\Resources\InvoicePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoicePayment extends EditRecord
{
    protected static string $resource = InvoicePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
