<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'reference',
        'paystack_reference',
        'type',
        'status',
        'total_amount',
        'platform_commission',
        'merchant_amount',
        'merchant_name',
        'merchant_email',
        'merchant_account',
        'merchant_bank',
        'paystack_subaccount',
        'settled_to_merchant',
        'settled_at',
        'settlement_reference',
        'customer_name',
        'customer_email',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'settled_at' => 'datetime',
        'settled_to_merchant' => 'boolean',
        'total_amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'merchant_amount' => 'decimal:2',
    ];

    /**
     * Get the invoice associated with this transaction
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PublicInvoice::class, 'invoice_id');
    }

    /**
     * Scope to get only commission transactions
     */
    public function scopeCommissions($query)
    {
        return $query->where('type', 'payment');
    }

    /**
     * Scope to get unsettled transactions
     */
    public function scopeUnsettled($query)
    {
        return $query->where('settled_to_merchant', false)
                     ->where('status', 'success');
    }

    /**
     * Scope to get settled transactions
     */
    public function scopeSettled($query)
    {
        return $query->where('settled_to_merchant', true);
    }

    /**
     * Scope to get successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return '₦' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted platform commission
     */
    public function getFormattedCommissionAttribute(): string
    {
        return '₦' . number_format($this->platform_commission, 2);
    }

    /**
     * Get formatted merchant amount
     */
    public function getFormattedMerchantAmountAttribute(): string
    {
        return '₦' . number_format($this->merchant_amount, 2);
    }
}
