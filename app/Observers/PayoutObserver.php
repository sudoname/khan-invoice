<?php

namespace App\Observers;

use App\Models\Payment\Payout;
use App\Models\Payment\LedgerEntry;
use Illuminate\Support\Facades\Log;

/**
 * PayoutObserver - Enforces accounting integrity rules
 *
 * CRITICAL SAFEGUARD: This observer prevents payouts from being
 * processed without corresponding ledger entries.
 */
class PayoutObserver
{
    /**
     * Handle the Payout "updating" event.
     *
     * This prevents a payout from being marked as PROCESSING or COMPLETED
     * if it doesn't have corresponding ledger entries.
     */
    public function updating(Payout $payout): bool
    {
        // If status is changing to PROCESSING or COMPLETED
        if ($payout->isDirty('status') && in_array($payout->status, ['PROCESSING', 'COMPLETED'])) {

            // Check if ledger entries exist for this payout
            $hasLedgerEntries = LedgerEntry::where('payout_id', $payout->id)
                ->where('entry_type', 'PAYOUT')
                ->where('account_type', 'DEBIT')
                ->exists();

            if (!$hasLedgerEntries) {
                Log::error('CRITICAL: Attempted to process payout without ledger entries', [
                    'payout_id' => $payout->id,
                    'reference' => $payout->reference,
                    'amount' => $payout->gross_amount,
                    'new_status' => $payout->status,
                    'old_status' => $payout->getOriginal('status'),
                    'user_id' => $payout->user_id,
                ]);

                // Prevent the update
                return false;
            }
        }

        return true;
    }

    /**
     * Handle the Payout "deleted" event.
     *
     * Prevent deletion of payouts that have been processed.
     */
    public function deleting(Payout $payout): bool
    {
        if (in_array($payout->status, ['PROCESSING', 'COMPLETED'])) {
            Log::warning('Attempted to delete processed payout', [
                'payout_id' => $payout->id,
                'reference' => $payout->reference,
                'status' => $payout->status,
            ]);

            // Prevent deletion of processed payouts
            return false;
        }

        return true;
    }

    /**
     * Handle the Payout "created" event.
     *
     * Log all payout creations for audit trail.
     */
    public function created(Payout $payout): void
    {
        Log::info('Payout created', [
            'payout_id' => $payout->id,
            'reference' => $payout->reference,
            'user_id' => $payout->user_id,
            'amount' => $payout->gross_amount,
            'status' => $payout->status,
            'has_ledger_entries' => LedgerEntry::where('payout_id', $payout->id)->exists(),
        ]);
    }
}
