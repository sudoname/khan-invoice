<?php

namespace App\Filament\Admin\Resources\InvoicePaymentResource\Pages;

use App\Filament\Admin\Resources\InvoicePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListInvoicePayments extends ListRecords
{
    protected static string $resource = InvoicePaymentResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Payments'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn ($query) => $query->where('reconciliation_status', 'PENDING'))
                ->badge(fn () => static::getResource()::getModel()::where('reconciliation_status', 'PENDING')->count()),
            'reconciled' => Tab::make('Reconciled')
                ->modifyQueryUsing(fn ($query) => $query->where('reconciliation_status', 'RECONCILED')),
            'disputed' => Tab::make('Disputed')
                ->modifyQueryUsing(fn ($query) => $query->where('reconciliation_status', 'DISPUTED'))
                ->badge(fn () => static::getResource()::getModel()::where('reconciliation_status', 'DISPUTED')->count())
                ->badgeColor('danger'),
        ];
    }
}
