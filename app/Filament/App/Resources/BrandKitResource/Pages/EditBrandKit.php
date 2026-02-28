<?php

namespace App\Filament\App\Resources\BrandKitResource\Pages;

use App\Filament\App\Resources\BrandKitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrandKit extends EditRecord
{
    protected static string $resource = BrandKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
