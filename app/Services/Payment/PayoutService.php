<?php

namespace App\Services\Payment;

use App\Models\Payment\MerchantAccount;
use App\Models\Payment\Payout;
use App\Models\Payment\LedgerEntry;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    public function __construct(
        protected PaystackService $paystackService
    ) {}

    /**
     * Create and process a payout request
     *
     * @param array $data
     * @return array
     */
    public function createPayout(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validate instant payout feature flag
            $payoutType = $data['payout_type'] ?? 'STANDARD';
            if ($payoutType === 'INSTANT' && !\App\Models\FeatureFlag::isEnabledForEnvironment('instant_payouts')) {
                return $this->error('Instant payouts are currently not available. Please contact support to enable this premium feature.');
            }

            // Validate merchant account
            $merchantAccount = MerchantAccount::find($data['merchant_account_id']);

            if (!$merchantAccount) {
                return $this->error('Merchant account not found');
            }

            if (!$merchantAccount->canReceivePayouts()) {
                return $this->error('Merchant account is not eligible for payouts. Account must be verified and active.');
            }

            if (!$merchantAccount->provider_recipient_code) {
                return $this->error('Merchant account has no Paystack recipient code. Please contact support.');
            }

            // Check available balance
            $availableBalance = $merchantAccount->getAvailableBalance();
            $requestedAmount = (float) $data['gross_amount'];

            if ($requestedAmount > $availableBalance) {
                return $this->error("Insufficient balance. Available: ₦" . number_format($availableBalance, 2) . ", Requested: ₦" . number_format($requestedAmount, 2));
            }

            // Check minimum payout amount
            if ($requestedAmount < $merchantAccount->minimum_payout) {
                return $this->error("Amount below minimum payout threshold of ₦" . number_format($merchantAccount->minimum_payout, 2));
            }

            // Calculate payout fee
            $payoutFee = $this->calculatePayoutFee($requestedAmount, $data['payout_type'] ?? 'STANDARD');
            $netAmount = $requestedAmount - $payoutFee;

            // Create payout record
            $payout = Payout::create([
                'user_id' => $merchantAccount->user_id,
                'merchant_account_id' => $merchantAccount->id,
                'reference' => $data['reference'] ?? Payout::generateReference($data['payout_type'] ?? 'STANDARD'),
                'gross_amount' => $requestedAmount,
                'payout_fee' => $payoutFee,
                'net_amount' => $netAmount,
                'currency' => 'NGN',
                'payout_type' => $data['payout_type'] ?? 'STANDARD',
                'status' => 'PENDING',
                'provider' => 'paystack',
                'settlement_date' => $data['settlement_date'] ?? now()->addDay(),
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'requires_approval' => $data['requires_approval'] ?? ($requestedAmount > 100000), // Require approval for > ₦100k
            ]);

            // Create ledger entries for payout
            $this->createPayoutLedgerEntries($payout, $merchantAccount);

            DB::commit();

            // If auto-approved, process immediately
            if (!$payout->requires_approval) {
                return $this->processPayout($payout->id);
            }

            return $this->success('Payout created and pending approval', [
                'payout' => $payout,
                'requires_approval' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayoutService::createPayout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            return $this->error('Failed to create payout: ' . $e->getMessage());
        }
    }

    /**
     * Process an approved payout
     *
     * @param int $payoutId
     * @return array
     */
    public function processPayout(int $payoutId): array
    {
        try {
            $payout = Payout::find($payoutId);

            if (!$payout) {
                return $this->error('Payout not found');
            }

            if ($payout->status !== 'PENDING') {
                return $this->error("Payout cannot be processed. Current status: {$payout->status}");
            }

            if ($payout->requires_approval && !$payout->approved_at) {
                return $this->error('Payout requires approval before processing');
            }

            $merchantAccount = $payout->merchantAccount;

            if (!$merchantAccount->provider_recipient_code) {
                $payout->markAsFailed('No Paystack recipient code configured');
                return $this->error('Merchant account has no Paystack recipient code');
            }

            // Mark as processing
            $payout->markAsProcessing();

            // Initiate transfer via Paystack
            $transferResult = $this->paystackService->initiateTransfer([
                'amount' => $payout->net_amount,
                'recipient' => $merchantAccount->provider_recipient_code,
                'reason' => "Payout for period " . ($payout->period_start ? $payout->period_start->format('M d') . ' - ' . $payout->period_end->format('M d, Y') : $payout->reference),
                'reference' => $payout->reference,
                'currency' => 'NGN',
            ]);

            if ($transferResult['status']) {
                // Transfer initiated successfully
                $payout->markAsCompleted([
                    'provider_reference' => $transferResult['data']['reference'] ?? null,
                    'provider_transfer_code' => $transferResult['data']['transfer_code'] ?? null,
                    'provider_response' => $transferResult['data'],
                ]);

                Log::info('Payout processed successfully', [
                    'payout_id' => $payout->id,
                    'amount' => $payout->net_amount,
                    'transfer_code' => $transferResult['data']['transfer_code'] ?? null,
                ]);

                return $this->success('Payout processed successfully', [
                    'payout' => $payout->fresh(),
                    'transfer' => $transferResult['data'],
                ]);
            } else {
                // Transfer failed
                $payout->markAsFailed($transferResult['message']);

                Log::error('Payout failed', [
                    'payout_id' => $payout->id,
                    'error' => $transferResult['message'],
                ]);

                return $this->error("Payout failed: {$transferResult['message']}");
            }

        } catch (\Exception $e) {
            if (isset($payout)) {
                $payout->markAsFailed($e->getMessage());
            }

            Log::error('PayoutService::processPayout failed', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to process payout: ' . $e->getMessage());
        }
    }

    /**
     * Create ledger entries for payout
     *
     * @param Payout $payout
     * @param MerchantAccount $merchantAccount
     * @return void
     */
    protected function createPayoutLedgerEntries(Payout $payout, MerchantAccount $merchantAccount): void
    {
        // Get current balance
        $currentBalance = $merchantAccount->getAvailableBalance();

        // Debit for payout amount
        $balanceAfterPayout = $currentBalance - $payout->gross_amount;
        LedgerEntry::create([
            'user_id' => $payout->user_id,
            'entry_type' => 'PAYOUT',
            'account_type' => 'DEBIT',
            'amount' => $payout->gross_amount,
            'balance_after' => $balanceAfterPayout,
            'currency' => 'NGN',
            'payout_id' => $payout->id,
            'description' => "Payout to {$merchantAccount->bank_name} account {$merchantAccount->account_number}",
            'reference' => LedgerEntry::generateReference('PAYOUT'),
            'entry_date' => now(),
        ]);

        // If there's a payout fee, record it
        if ($payout->payout_fee > 0) {
            $balanceAfterFee = $balanceAfterPayout; // Fee is already deducted from gross
            LedgerEntry::create([
                'user_id' => $payout->user_id,
                'entry_type' => $payout->payout_type === 'INSTANT' ? 'INSTANT_PAYOUT_FEE' : 'PLATFORM_FEE',
                'account_type' => 'DEBIT',
                'amount' => $payout->payout_fee,
                'balance_after' => $balanceAfterFee,
                'currency' => 'NGN',
                'payout_id' => $payout->id,
                'description' => $payout->payout_type === 'INSTANT' ? 'Instant payout fee (2%)' : 'Standard payout fee',
                'reference' => LedgerEntry::generateReference($payout->payout_type === 'INSTANT' ? 'INSTANT_PAYOUT_FEE' : 'PLATFORM_FEE'),
                'entry_date' => now(),
            ]);
        }
    }

    /**
     * Calculate payout fee based on type
     *
     * @param float $amount
     * @param string $type
     * @return float
     */
    protected function calculatePayoutFee(float $amount, string $type): float
    {
        return match($type) {
            'INSTANT' => round($amount * 0.02, 2), // 2% for instant payouts
            'STANDARD' => 0.00, // Free for standard payouts
            default => 0.00,
        };
    }

    /**
     * Approve a payout
     *
     * @param int $payoutId
     * @param int $adminId
     * @return array
     */
    public function approvePayout(int $payoutId, int $adminId): array
    {
        try {
            $payout = Payout::find($payoutId);

            if (!$payout) {
                return $this->error('Payout not found');
            }

            if ($payout->status !== 'PENDING') {
                return $this->error("Payout cannot be approved. Current status: {$payout->status}");
            }

            if (!$payout->requires_approval) {
                return $this->error('This payout does not require approval');
            }

            if ($payout->approved_at) {
                return $this->error('Payout has already been approved');
            }

            $payout->approve(\App\Models\User::find($adminId));

            // Now process the payout
            return $this->processPayout($payoutId);

        } catch (\Exception $e) {
            Log::error('PayoutService::approvePayout failed', [
                'payout_id' => $payoutId,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to approve payout: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a payout
     *
     * @param int $payoutId
     * @param string $reason
     * @return array
     */
    public function cancelPayout(int $payoutId, string $reason): array
    {
        try {
            DB::beginTransaction();

            $payout = Payout::find($payoutId);

            if (!$payout) {
                return $this->error('Payout not found');
            }

            if (!in_array($payout->status, ['PENDING', 'PROCESSING'])) {
                return $this->error("Payout cannot be cancelled. Current status: {$payout->status}");
            }

            // Mark as failed
            $payout->markAsFailed("Cancelled: $reason");

            // Reverse ledger entries by creating credit entries
            $this->reversePayoutLedgerEntries($payout);

            DB::commit();

            return $this->success('Payout cancelled successfully', [
                'payout' => $payout->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayoutService::cancelPayout failed', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to cancel payout: ' . $e->getMessage());
        }
    }

    /**
     * Reverse ledger entries for cancelled payout
     *
     * @param Payout $payout
     * @return void
     */
    protected function reversePayoutLedgerEntries(Payout $payout): void
    {
        $currentBalance = $payout->merchantAccount->getAvailableBalance();

        // Credit back the gross amount
        $balanceAfterReversal = $currentBalance + $payout->gross_amount;
        LedgerEntry::create([
            'user_id' => $payout->user_id,
            'entry_type' => 'ADJUSTMENT',
            'account_type' => 'CREDIT',
            'amount' => $payout->gross_amount,
            'balance_after' => $balanceAfterReversal,
            'currency' => 'NGN',
            'payout_id' => $payout->id,
            'description' => "Reversal of cancelled payout {$payout->reference}",
            'reference' => LedgerEntry::generateReference('ADJUSTMENT'),
            'entry_date' => now(),
        ]);
    }

    /**
     * Get payout statistics for a user
     *
     * @param int $userId
     * @return array
     */
    public function getPayoutStats(int $userId): array
    {
        $stats = [
            'total_payouts' => Payout::where('user_id', $userId)->count(),
            'total_amount' => Payout::where('user_id', $userId)
                ->where('status', 'COMPLETED')
                ->sum('net_amount'),
            'pending_payouts' => Payout::where('user_id', $userId)
                ->where('status', 'PENDING')
                ->count(),
            'pending_amount' => Payout::where('user_id', $userId)
                ->where('status', 'PENDING')
                ->sum('gross_amount'),
            'last_payout' => Payout::where('user_id', $userId)
                ->where('status', 'COMPLETED')
                ->latest('completed_at')
                ->first(),
        ];

        return $stats;
    }

    /**
     * Success response helper
     */
    protected function success(string $message, array $data = []): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * Error response helper
     */
    protected function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
    }
}
