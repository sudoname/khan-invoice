<?php

namespace App\Filament\Admin\Resources\InvoicePaymentResource\Pages;

use App\Filament\Admin\Resources\InvoicePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoicePayment extends ViewRecord
{
    protected static string $resource = InvoicePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
