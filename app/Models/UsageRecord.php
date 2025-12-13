<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'type',
        'quantity',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the usage record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription for this usage record
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Scope to filter by usage type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by date period
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's usage
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get this month's usage
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
                     ->whereMonth('created_at', now()->month);
    }

    /**
     * Get formatted type name
     */
    public function getFormattedTypeAttribute(): string
    {
        return match($this->type) {
            'invoice_created' => 'Invoice Created',
            'customer_created' => 'Customer Created',
            'sms_sent' => 'SMS Sent',
            'whatsapp_sent' => 'WhatsApp Sent',
            'api_request' => 'API Request',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
