<?php

namespace App\Filament\Admin\Resources\PublicInvoiceResource\Pages;

use App\Filament\Admin\Resources\PublicInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPublicInvoice extends ViewRecord
{
    protected static string $resource = PublicInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('view_public')
                ->label('View Public Page')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->url(fn (): string => route('public-invoice.show', $this->record->public_id))
                ->openUrlInNewTab(),
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('public-invoice.download', $this->record->public_id))
                ->openUrlInNewTab(),
        ];
    }
}
