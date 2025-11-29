<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'business_profile_id',
        'customer_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'status',
        'currency',
        'sub_total',
        'discount_total',
        'vat_rate',
        'vat_amount',
        'wht_rate',
        'wht_amount',
        'total_amount',
        'amount_paid',
        'notes',
        'footer',
        'public_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->public_id)) {
                $invoice->public_id = Str::random(12);
            }

            // Generate sequential invoice number if not set
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate a unique sequential invoice number
     * Format: INV-YYYY-NNNNNNNN (8-digit sequential number)
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = 'INV-' . $year . '-';

        // Get the count of invoices for the current year
        $count = static::whereYear('created_at', $year)->count();

        // Generate sequential number with 8-digit padding
        $sequentialNumber = str_pad($count + 1, 8, '0', STR_PAD_LEFT);

        $invoiceNumber = $prefix . $sequentialNumber;

        // Ensure uniqueness (in case of race conditions)
        while (static::where('invoice_number', $invoiceNumber)->exists()) {
            $count++;
            $sequentialNumber = str_pad($count + 1, 8, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $sequentialNumber;
        }

        return $invoiceNumber;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Calculate totals
    public function calculateTotals(): void
    {
        $this->sub_total = $this->items->sum('line_total');
        $this->vat_amount = $this->sub_total * ($this->vat_rate / 100);
        $this->wht_amount = $this->wht_rate ? $this->sub_total * ($this->wht_rate / 100) : 0;
        $this->total_amount = $this->sub_total + $this->vat_amount - $this->wht_amount - $this->discount_total;
        $this->save();
    }

    // Query Scopes
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'overdue', 'partially_paid']);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }
}
