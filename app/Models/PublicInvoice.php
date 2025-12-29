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
        'company_logo',
        'from_bank_name',
        'from_account_number',
        'from_account_name',
        'from_account_type',
        'paystack_subaccount_code',
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
        'paid_at',
        'simple_mode',
        'sent_at',
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
        'paid_at' => 'datetime',
        'simple_mode' => 'boolean',
        'sent_at' => 'datetime',
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

    /**
     * Get the invoice status based on payment and sent status
     * Status logic:
     * - Paid: payment_status is 'paid'
     * - Draft: not sent (sent_at is null)
     * - Sent: sent_at is set but due date is in future
     * - Due: sent and due date is in future
     * - Overdue: sent and due date has passed
     */
    public function getStatusAttribute(): string
    {
        if ($this->payment_status === 'paid') {
            return 'paid';
        }

        if (!$this->sent_at) {
            return 'draft';
        }

        if ($this->due_date && $this->due_date->isFuture()) {
            return 'due';
        }

        return 'overdue';
    }

    /**
     * Get the status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'due' => 'yellow',
            'overdue' => 'red',
            'paid' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get the status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'due' => 'Due',
            'overdue' => 'Overdue',
            'paid' => 'Paid',
            default => 'Unknown',
        };
    }

    /**
     * Check if invoice can be marked as sent
     */
    public function canMarkAsSent(): bool
    {
        return !$this->sent_at && $this->payment_status !== 'paid';
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): bool
    {
        if ($this->canMarkAsSent()) {
            $this->sent_at = now();
            return $this->save();
        }
        return false;
    }
}
