<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_profile_id',
        'customer_id',
        'income_number',
        'income_date',
        'category',
        'description',
        'payment_method',
        'reference_number',
        'currency',
        'amount',
        'tax_amount',
        'total_amount',
        'notes',
        'receipt_url',
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the income.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business profile associated with the income.
     */
    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the customer associated with the income (optional).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Generate a sequential income number
     */
    public static function generateIncomeNumber(): string
    {
        $year = now()->year;
        $lastIncome = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastIncome ? ((int) substr($lastIncome->income_number, -8)) + 1 : 1;

        return sprintf('INC-%d-%08d', $year, $nextNumber);
    }

    /**
     * Get category options
     */
    public static function getCategoryOptions(): array
    {
        return [
            'cash_sales' => 'Cash Sales',
            'service_revenue' => 'Service Revenue',
            'product_sales' => 'Product Sales',
            'commission' => 'Commission',
            'interest' => 'Interest Income',
            'rental_income' => 'Rental Income',
            'consulting' => 'Consulting Fees',
            'refund' => 'Refund',
            'other' => 'Other Income',
        ];
    }

    /**
     * Get payment method options
     */
    public static function getPaymentMethodOptions(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'card' => 'Card Payment',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Cheque',
            'other' => 'Other',
        ];
    }
}
