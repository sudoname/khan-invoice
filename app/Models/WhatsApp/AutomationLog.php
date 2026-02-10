<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'user_id',
        'automation_rule_id',
        'wa_conversation_id',
        'target_type',
        'target_id',
        'action',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'success',
    ];

    /**
     * Get the user that owns the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the automation rule.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'wa_conversation_id');
    }

    /**
     * Log a successful action.
     */
    public static function logSuccess(
        int $userId,
        string $action,
        ?int $ruleId = null,
        ?int $conversationId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $meta = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'automation_rule_id' => $ruleId,
            'wa_conversation_id' => $conversationId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'status' => 'success',
            'meta' => $meta,
        ]);
    }

    /**
     * Log a skipped action.
     */
    public static function logSkipped(
        int $userId,
        string $action,
        string $reason,
        ?int $ruleId = null,
        ?string $targetType = null,
        ?int $targetId = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'automation_rule_id' => $ruleId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'status' => 'skipped',
            'meta' => ['reason' => $reason],
        ]);
    }

    /**
     * Log a failed action.
     */
    public static function logFailed(
        int $userId,
        string $action,
        string $error,
        ?int $ruleId = null,
        ?int $conversationId = null,
        ?string $targetType = null,
        ?int $targetId = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'automation_rule_id' => $ruleId,
            'wa_conversation_id' => $conversationId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'status' => 'failed',
            'meta' => ['error' => $error],
        ]);
    }
}
