<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaMessage extends Model
{
    protected $fillable = [
        'user_id',
        'wa_conversation_id',
        'direction',
        'message_type',
        'body',
        'payload',
        'provider_message_id',
        'status',
        'error',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $attributes = [
        'direction' => 'outbound',
        'message_type' => 'text',
        'status' => 'queued',
    ];

    /**
     * Get the user that owns the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'wa_conversation_id');
    }

    /**
     * Check if message is inbound.
     */
    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    /**
     * Check if message is outbound.
     */
    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    /**
     * Mark message as sent.
     */
    public function markSent(string $providerMessageId): void
    {
        $this->update([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark message as delivered.
     */
    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as read.
     */
    public function markRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark message as failed.
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }

    /**
     * Create an inbound message.
     */
    public static function createInbound(
        int $userId,
        int $conversationId,
        string $body,
        string $providerMessageId,
        array $payload = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'wa_conversation_id' => $conversationId,
            'direction' => 'inbound',
            'body' => $body,
            'provider_message_id' => $providerMessageId,
            'payload' => $payload,
            'status' => 'delivered', // Inbound messages are already delivered
        ]);
    }

    /**
     * Create an outbound message.
     */
    public static function createOutbound(
        int $userId,
        int $conversationId,
        string $body,
        string $messageType = 'text',
        array $payload = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'wa_conversation_id' => $conversationId,
            'direction' => 'outbound',
            'message_type' => $messageType,
            'body' => $body,
            'payload' => $payload,
            'status' => 'queued',
        ]);
    }
}
