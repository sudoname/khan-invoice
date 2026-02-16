<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'account_type',
        'verification_status',
        'verified_at',
        'verification_notes',
        'is_primary',
        'settlement_schedule',
        'minimum_payout',
        'provider_recipient_code',
        'provider_metadata',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
    ];

    protected $casts = [
        'minimum_payout' => 'decimal:2',
        'provider_metadata' => 'array',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the merchant account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get payouts for this merchant account
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Check if account is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'VERIFIED';
    }

    /**
     * Check if account can receive payouts
     */
    public function canReceivePayouts(): bool
    {
        return $this->is_active && $this->isVerified();
    }

    /**
     * Get current available balance (in currency units, not kobo)
     * Note: Balance is tracked per user, not per merchant account
     *
     * IMPORTANT: Calculates balance using accounting equation (Credits - Debits)
     * instead of relying on balance_after field which may be outdated if entries
     * were added out of order or ledger was corrupted.
     */
    public function getAvailableBalance(): float
    {
        return (float) LedgerEntry::where('user_id', $this->user_id)
            ->sum(\DB::raw('CASE
                WHEN account_type = "CREDIT" THEN amount
                WHEN account_type = "DEBIT" THEN -amount
                ELSE 0
            END'));
    }

    /**
     * Get total pending payout amount for THIS merchant account
     */
    public function getPendingPayoutAmount(): float
    {
        return (float) Payout::where('merchant_account_id', $this->id)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->sum('gross_amount');
    }

    /**
     * Get total completed payout amount for THIS merchant account
     */
    public function getCompletedPayoutAmount(): float
    {
        return (float) Payout::where('merchant_account_id', $this->id)
            ->where('status', 'COMPLETED')
            ->sum('net_amount');
    }

    /**
     * Get available balance after subtracting pending payouts
     */
    public function getAvailableBalanceAfterPending(): float
    {
        return $this->getAvailableBalance() - $this->getPendingPayoutAmount();
    }

    /**
     * Mark account as verified
     */
    public function markAsVerified(string $notes = null): void
    {
        $this->update([
            'verification_status' => 'VERIFIED',
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Deactivate account
     */
    public function deactivate(string $reason): void
    {
        $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);
    }
}
