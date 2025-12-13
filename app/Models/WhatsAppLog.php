<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'user_id',
        'recipient_phone',
        'message_type',
        'message_content',
        'status',
        'provider_message_id',
        'error_message',
        'cost',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
    ];

    /**
     * Get the user that owns the WhatsApp log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by message type.
     */
    public function scopeByMessageType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope: Filter failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
