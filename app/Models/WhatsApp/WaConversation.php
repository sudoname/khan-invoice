<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaConversation extends Model
{
    protected $fillable = [
        'user_id',
        'wa_contact_id',
        'status',
        'state',
        'last_intent',
        'context',
        'last_message_at',
        'last_outbound_at',
        'human_handoff',
    ];

    protected $casts = [
        'context' => 'array',
        'last_message_at' => 'datetime',
        'last_outbound_at' => 'datetime',
        'human_handoff' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'open',
        'state' => 'idle',
        'human_handoff' => false,
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact for this conversation.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(WaContact::class, 'wa_contact_id');
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class);
    }

    /**
     * Get recent messages (last N).
     */
    public function recentMessages(int $limit = 10)
    {
        return $this->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Update conversation state.
     */
    public function updateState(string $state, ?array $contextMerge = null): void
    {
        $data = ['state' => $state];

        if ($contextMerge) {
            $data['context'] = array_merge($this->context ?? [], $contextMerge);
        }

        $this->update($data);
    }

    /**
     * Update last message timestamp.
     */
    public function touchLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Mark as requiring human handoff.
     */
    public function requestHandoff(string $reason = null): void
    {
        $this->update([
            'human_handoff' => true,
            'status' => 'handoff',
            'context' => array_merge($this->context ?? [], ['handoff_reason' => $reason]),
        ]);
    }

    /**
     * Close the conversation.
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'state' => 'idle',
        ]);
    }

    /**
     * Get context value by key.
     */
    public function getContextValue(string $key, $default = null)
    {
        return data_get($this->context, $key, $default);
    }

    /**
     * Set context value by key.
     */
    public function setContextValue(string $key, $value): void
    {
        $context = $this->context ?? [];
        data_set($context, $key, $value);
        $this->update(['context' => $context]);
    }
}
