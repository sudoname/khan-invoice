<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicInvoice extends Model
{
    protected $fillable = [
        'public_id',
        'invoice_number',
        'from_name',
        'from_email',
        'from_phone',
        'from_address',
        'from_bank_name',
        'from_account_number',
        'from_account_name',
        'from_account_type',
        'to_name',
        'to_email',
        'to_phone',
        'to_address',
        'issue_date',
        'due_date',
        'items',
        'subtotal',
        'vat_percentage',
        'vat_amount',
        'wht_percentage',
        'wht_amount',
        'discount_percentage',
        'discount_amount',
        'total_amount',
        'notes',
        'payment_status',
        'amount_paid',
    ];

    protected $casts = [
        'items' => 'array',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'wht_percentage' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Generate a unique public ID for the invoice
     */
    public static function generatePublicId(): string
    {
        do {
            $publicId = Str::random(12);
        } while (self::where('public_id', $publicId)->exists());

        return $publicId;
    }

    /**
     * Get the public URL for this invoice
     */
    public function getPublicUrlAttribute(): string
    {
        return route('public-invoice.show', $this->public_id);
    }

    /**
     * Get the payment URL for this invoice
     */
    public function getPaymentUrlAttribute(): string
    {
        return route('public-invoice.pay', $this->public_id);
    }
}
