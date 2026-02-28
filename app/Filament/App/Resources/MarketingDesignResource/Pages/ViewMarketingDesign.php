<?php

namespace App\Filament\App\Resources\MarketingDesignResource\Pages;

use App\Filament\App\Resources\MarketingDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMarketingDesign extends ViewRecord
{
    protected static string $resource = MarketingDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Download PNG')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->isCompleted())
                ->url(fn () => $this->record->rendered_url)
                ->openUrlInNewTab()
                ->after(fn () => $this->record->incrementDownloadCount()),

            Actions\DeleteAction::make(),
        ];
    }
}
