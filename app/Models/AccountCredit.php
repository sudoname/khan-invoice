<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCredit extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'type',
        'amount',
        'currency',
        'status',
        'description',
        'metadata',
        'expires_at',
        'used_at',
        'used_in_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function usedInTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'used_in_transaction_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function markAsUsed(int $transactionId = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_in_transaction_id' => $transactionId,
        ]);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
