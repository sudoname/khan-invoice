<?php

namespace App\Filament\App\Resources\BrandKitResource\Pages;

use App\Filament\App\Resources\BrandKitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrandKit extends CreateRecord
{
    protected static string $resource = BrandKitResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
