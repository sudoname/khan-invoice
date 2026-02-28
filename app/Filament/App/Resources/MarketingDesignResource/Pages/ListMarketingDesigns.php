<?php

namespace App\Filament\App\Resources\MarketingDesignResource\Pages;

use App\Filament\App\Resources\MarketingDesignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarketingDesigns extends ListRecords
{
    protected static string $resource = MarketingDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('Create Design')
                ->icon('heroicon-m-plus')
                ->url(fn (): string => MarketingDesignResource::getUrl('create')),
        ];
    }
}
