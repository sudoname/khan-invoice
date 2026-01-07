<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeatureFlag extends ViewRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
