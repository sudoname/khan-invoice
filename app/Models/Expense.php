<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_profile_id',
        'vendor_id',
        'expense_number',
        'expense_date',
        'due_date',
        'category',
        'description',
        'reference_number',
        'payment_method',
        'status',
        'currency',
        'amount',
        'tax_amount',
        'total_amount',
        'notes',
        'receipt_url',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            // Generate sequential expense number if not set
            if (empty($expense->expense_number)) {
                $expense->expense_number = static::generateExpenseNumber();
            }

            // Calculate total amount if not set
            if ($expense->total_amount === null || $expense->total_amount === 0) {
                $expense->total_amount = $expense->amount + ($expense->tax_amount ?? 0);
            }
        });

        static::updating(function ($expense) {
            // Recalculate total amount
            $expense->total_amount = $expense->amount + ($expense->tax_amount ?? 0);
        });
    }

    /**
     * Generate a unique sequential expense number
     * Format: EXP-YYYY-NNNNNNNN (8-digit sequential number)
     */
    public static function generateExpenseNumber(): string
    {
        $year = date('Y');
        $prefix = 'EXP-' . $year . '-';

        // Get the count of expenses for the current year
        $count = static::whereYear('created_at', $year)->count();

        // Generate sequential number with 8-digit padding
        $sequentialNumber = str_pad($count + 1, 8, '0', STR_PAD_LEFT);

        $expenseNumber = $prefix . $sequentialNumber;

        // Ensure uniqueness (in case of race conditions)
        while (static::where('expense_number', $expenseNumber)->exists()) {
            $count++;
            $sequentialNumber = str_pad($count + 1, 8, '0', STR_PAD_LEFT);
            $expenseNumber = $prefix . $sequentialNumber;
        }

        return $expenseNumber;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // Query scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }
}
