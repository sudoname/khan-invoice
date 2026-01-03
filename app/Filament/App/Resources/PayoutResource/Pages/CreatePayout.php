<?php

namespace App\Filament\App\Resources\PayoutResource\Pages;

use App\Filament\App\Resources\PayoutResource;
use App\Models\Payment\MerchantAccount;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure currency is set
        $data['currency'] = 'NGN';

        // Set status
        $data['status'] = 'PENDING';

        // Determine if approval is required (for large amounts or instant payouts)
        $data['requires_approval'] = $data['payout_type'] === 'INSTANT' || $data['gross_amount'] >= 100000;

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Validate that user has a verified account
        $account = MerchantAccount::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('verification_status', 'VERIFIED')
            ->first();

        if (!$account) {
            Notification::make()
                ->danger()
                ->title('No verified bank account')
                ->body('You must add and verify a bank account before requesting a payout.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validate available balance
        $requestedAmount = $this->data['gross_amount'];
        $availableBalance = $account->getAvailableBalance();

        if ($requestedAmount > $availableBalance) {
            Notification::make()
                ->danger()
                ->title('Insufficient balance')
                ->body("You can only withdraw up to ₦" . number_format($availableBalance, 2) . " from your current balance.")
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Payout requested successfully')
            ->body("Your payout of ₦" . number_format($record->net_amount, 2) . " is being processed. Reference: {$record->reference}")
            ->persistent();
    }
}
