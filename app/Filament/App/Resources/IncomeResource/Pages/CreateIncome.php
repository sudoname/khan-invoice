<?php

namespace App\Filament\App\Resources\IncomeResource\Pages;

use App\Filament\App\Resources\IncomeResource;
use App\Models\Income;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncome extends CreateRecord
{
    protected static string $resource = IncomeResource::class;

    /**
     * Generate income number just before creating to avoid race conditions
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['income_number'] = Income::generateIncomeNumber();
        return $data;
    }
}
