<?php

namespace App\Filament\App\Resources\BrandKitResource\Pages;

use App\Filament\App\Resources\BrandKitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrandKits extends ListRecords
{
    protected static string $resource = BrandKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
