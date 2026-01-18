<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'entry_type',
        'account_type',
        'amount',
        'balance_after',
        'currency',
        'invoice_payment_id',
        'invoice_id',
        'payout_id',
        'description',
        'reference',
        'metadata',
        'is_reconciled',
        'reconciled_at',
        'entry_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'entry_date' => 'datetime',
    ];

    /**
     * Get the user that owns this ledger entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoice payment (if applicable)
     */
    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class);
    }

    /**
     * Get the payout (if applicable)
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * Check if entry is a credit
     */
    public function isCredit(): bool
    {
        return $this->account_type === 'CREDIT';
    }

    /**
     * Check if entry is a debit
     */
    public function isDebit(): bool
    {
        return $this->account_type === 'DEBIT';
    }

    /**
     * Mark as reconciled
     */
    public function markAsReconciled(): void
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_at' => now(),
        ]);
    }

    /**
     * Generate unique reference
     */
    public static function generateReference(string $type): string
    {
        $prefix = match($type) {
            'PAYMENT', 'PAYMENT_RECEIVED' => 'PMT',
            'GATEWAY_FEE' => 'GWF',
            'PLATFORM_FEE' => 'FEE',
            'PAYOUT' => 'PYT',
            'REFUND' => 'RFD',
            'CHARGEBACK' => 'CHB',
            'ADJUSTMENT' => 'ADJ',
            'INSTANT_PAYOUT_FEE' => 'IPF',
            default => 'LDG',
        };

        return $prefix . '-' . strtoupper(uniqid());
    }
}
