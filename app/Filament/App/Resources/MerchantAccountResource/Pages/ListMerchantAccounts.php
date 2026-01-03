<?php

namespace App\Filament\App\Resources\MerchantAccountResource\Pages;

use App\Filament\App\Resources\MerchantAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchantAccounts extends ListRecords
{
    protected static string $resource = MerchantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Bank Account'),
        ];
    }
}
