<?php

namespace App\Filament\Admin\Resources\MerchantAccountResource\Pages;

use App\Filament\Admin\Resources\MerchantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchantAccounts extends ListRecords
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admins don't create accounts - merchants do
        ];
    }
}
