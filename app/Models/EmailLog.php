<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'message_type',
        'body_excerpt',
        'status',
        'error_message',
        'provider',
        'message_id',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByMessageType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToRecipient($query, string $email)
    {
        return $query->where('recipient_email', $email);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
