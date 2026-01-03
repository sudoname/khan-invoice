<?php

namespace App\Models\Payment;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentAttempt extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'provider',
        'channel',
        'reference',
        'authorization_url',
        'status',
        'amount',
        'currency',
        'fees',
        'net_amount',
        'customer_email',
        'customer_phone',
        'customer_name',
        'metadata',
        'failure_reason',
        'initiated_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the invoice for this payment attempt
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the successful payment for this attempt
     */
    public function payment(): HasOne
    {
        return $this->hasOne(InvoicePayment::class);
    }

    /**
     * Check if attempt is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'SUCCESS';
    }

    /**
     * Check if attempt is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['INITIATED', 'PENDING']);
    }

    /**
     * Check if attempt is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['FAILED', 'CANCELLED', 'ABANDONED']);
    }

    /**
     * Mark attempt as successful
     */
    public function markAsSuccessful(array $data = []): void
    {
        $this->update([
            'status' => 'SUCCESS',
            'completed_at' => now(),
            'fees' => $data['fees'] ?? $this->fees,
            'net_amount' => $data['net_amount'] ?? $this->net_amount,
            'metadata' => array_merge($this->metadata ?? [], $data['metadata'] ?? []),
        ]);
    }

    /**
     * Mark attempt as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'FAILED',
            'completed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
