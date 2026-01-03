<?php

namespace App\Filament\App\Resources\PayoutResource\Pages;

use App\Filament\App\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Payment\MerchantAccount;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        // Check if user has a verified bank account
        $hasVerifiedAccount = MerchantAccount::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('verification_status', 'VERIFIED')
            ->exists();

        if (!$hasVerifiedAccount) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('Request Payout')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Show balance widget at top of page
        return [];
    }
}
