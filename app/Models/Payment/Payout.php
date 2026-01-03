<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payout extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_account_id',
        'reference',
        'gross_amount',
        'payout_fee',
        'net_amount',
        'currency',
        'payout_type',
        'status',
        'provider',
        'provider_reference',
        'provider_transfer_code',
        'provider_response',
        'failure_reason',
        'initiated_at',
        'completed_at',
        'failed_at',
        'settlement_date',
        'period_start',
        'period_end',
        'requires_approval',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'payout_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'provider_response' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'settlement_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the user that owns this payout
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the merchant account
     */
    public function merchantAccount(): BelongsTo
    {
        return $this->belongsTo(MerchantAccount::class);
    }

    /**
     * Get the approver (admin user)
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get ledger entries for this payout
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Check if payout is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if payout is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    /**
     * Check if payout needs approval
     */
    public function needsApproval(): bool
    {
        return $this->requires_approval && !$this->approved_at;
    }

    /**
     * Approve payout
     */
    public function approve(User $admin): void
    {
        $this->update([
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark payout as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'PROCESSING',
            'initiated_at' => now(),
        ]);
    }

    /**
     * Mark payout as completed
     */
    public function markAsCompleted(array $data = []): void
    {
        $this->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'provider_reference' => $data['provider_reference'] ?? $this->provider_reference,
            'provider_transfer_code' => $data['provider_transfer_code'] ?? $this->provider_transfer_code,
            'provider_response' => $data['provider_response'] ?? $this->provider_response,
        ]);
    }

    /**
     * Mark payout as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'FAILED',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Generate unique reference
     */
    public static function generateReference(string $type = 'MANUAL'): string
    {
        $prefix = match($type) {
            'INSTANT' => 'INST',
            'STANDARD' => 'STD',
            default => 'MAN',
        };

        return 'PO-' . $prefix . '-' . strtoupper(uniqid());
    }
}
