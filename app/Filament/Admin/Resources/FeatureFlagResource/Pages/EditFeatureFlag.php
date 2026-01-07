<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
