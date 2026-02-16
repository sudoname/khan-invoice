<?php

namespace App\Filament\App\Resources\PayoutResource\Pages;

use App\Filament\App\Resources\PayoutResource;
use App\Models\Payment\MerchantAccount;
use App\Models\FeatureFlag;
use App\Services\Payment\PayoutService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePayout extends CreateRecord
{
    protected static string $resource = PayoutResource::class;

    /**
     * CRITICAL: Override the create process to use PayoutService
     * This ensures proper ledger entries and accounting integrity
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Use PayoutService to create the payout properly
        $payoutService = app(PayoutService::class);

        $result = $payoutService->createPayout([
            'merchant_account_id' => $data['merchant_account_id'],
            'gross_amount' => $data['gross_amount'],
            'payout_type' => $data['payout_type'],
            'reference' => $data['reference'] ?? null,
        ]);

        if (!$result['success']) {
            Notification::make()
                ->danger()
                ->title('Payout Failed')
                ->body($result['message'])
                ->persistent()
                ->send();

            $this->halt();
        }

        // Return the created payout record
        return $result['data']['payout'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // PayoutService handles these fields, so we just pass through
        // No need to set currency, status, requires_approval here
        return $data;
    }

    protected function beforeCreate(): void
    {
        // Validate instant payout feature flag
        if ($this->data['payout_type'] === 'INSTANT' && !FeatureFlag::isEnabledForEnvironment('instant_payouts')) {
            Notification::make()
                ->danger()
                ->title('Feature not available')
                ->body('Instant payouts are currently not available. Please contact support to enable this premium feature.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validate that user has a verified account
        $account = MerchantAccount::find($this->data['merchant_account_id']);

        if (!$account || $account->user_id !== auth()->id()) {
            Notification::make()
                ->danger()
                ->title('Invalid account')
                ->body('The selected bank account is invalid or does not belong to you.')
                ->persistent()
                ->send();

            $this->halt();
        }

        if (!$account->canReceivePayouts()) {
            Notification::make()
                ->danger()
                ->title('Account not eligible')
                ->body('The selected bank account is not eligible for payouts. It must be verified and active.')
                ->persistent()
                ->send();

            $this->halt();
        }

        // Validate available balance
        $requestedAmount = (float) $this->data['gross_amount'];
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

        // Validate minimum payout
        if ($requestedAmount < $account->minimum_payout) {
            Notification::make()
                ->danger()
                ->title('Below minimum')
                ->body("Minimum payout amount is ₦" . number_format($account->minimum_payout, 2))
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

        if (!$record) {
            return null;
        }

        $message = "Your payout of ₦" . number_format($record->net_amount, 2) . " has been requested. Reference: {$record->reference}";

        if ($record->requires_approval) {
            $message .= " (Pending admin approval)";
        } else if ($record->status === 'PROCESSING') {
            $message .= " (Being processed)";
        }

        return Notification::make()
            ->success()
            ->title('Payout Created Successfully')
            ->body($message)
            ->persistent();
    }
}
