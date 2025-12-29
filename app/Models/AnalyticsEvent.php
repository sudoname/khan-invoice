<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'name',
        'occurred_at',
        'path',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'session_id',
        'anonymous_id',
        'user_id',
        'properties',
        'ip_hash',
        'user_agent',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'properties' => 'array',
    ];

    /**
     * Get the user that owns the event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by event name
     */
    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by session
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
