<?php

namespace App\Filament\Admin\Resources\PublicInvoiceResource\Pages;

use App\Filament\Admin\Resources\PublicInvoiceResource;
use App\Filament\Admin\Resources\PublicInvoiceResource\Widgets\PublicInvoiceStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPublicInvoices extends ListRecords
{
    protected static string $resource = PublicInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_public_invoice')
                ->label('Create Public Invoice')
                ->icon('heroicon-o-plus')
                ->url(route('public-invoice.create'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PublicInvoiceStats::class,
        ];
    }
}
