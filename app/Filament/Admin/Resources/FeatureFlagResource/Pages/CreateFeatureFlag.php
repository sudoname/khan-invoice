<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;
}
