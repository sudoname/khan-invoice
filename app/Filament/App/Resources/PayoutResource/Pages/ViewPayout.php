<?php

namespace App\Filament\App\Resources\PayoutResource\Pages;

use App\Filament\App\Resources\PayoutResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions - payouts cannot be edited or deleted
        ];
    }
}
