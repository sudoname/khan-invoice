<?php

namespace App\Models\Payment;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InvoicePayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'payment_attempt_id',
        'amount_paid',
        'fees_paid',
        'net_received',
        'currency',
        'payment_method',
        'payment_reference',
        'payment_metadata',
        'reconciliation_status',
        'reconciled_at',
        'paid_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'fees_paid' => 'decimal:2',
        'net_received' => 'decimal:2',
        'payment_metadata' => 'array',
        'reconciled_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the invoice for this payment
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment attempt
     */
    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    /**
     * Get ledger entries for this payment
     */
    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'related');
    }

    /**
     * Check if payment is reconciled
     */
    public function isReconciled(): bool
    {
        return $this->reconciliation_status === 'RECONCILED';
    }

    /**
     * Mark payment as reconciled
     */
    public function markAsReconciled(): void
    {
        $this->update([
            'reconciliation_status' => 'RECONCILED',
            'reconciled_at' => now(),
        ]);
    }

    /**
     * Get the net amount received after fees
     */
    public function getNetAmount(): float
    {
        return (float) $this->net_received;
    }
}
