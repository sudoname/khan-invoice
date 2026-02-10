<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'user_id',
        'wa_contact_id',
        'source',
        'stage',
        'score',
        'product_interest',
        'notes',
        'last_activity_at',
        'assigned_to',
    ];

    protected $casts = [
        'score' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    protected $attributes = [
        'source' => 'whatsapp',
        'stage' => 'new',
        'score' => 0,
    ];

    /**
     * Get the user that owns the lead.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the WhatsApp contact associated with this lead.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(WaContact::class, 'wa_contact_id');
    }

    /**
     * Get the user assigned to this lead.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Update lead stage.
     */
    public function updateStage(string $stage): void
    {
        $this->update([
            'stage' => $stage,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Update lead score.
     */
    public function updateScore(int $score): void
    {
        $this->update(['score' => max(0, min(100, $score))]);
    }

    /**
     * Increment lead score.
     */
    public function incrementScore(int $points): void
    {
        $newScore = $this->score + $points;
        $this->updateScore($newScore);
    }

    /**
     * Touch last activity.
     */
    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Assign to a user.
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Qualify the lead.
     */
    public function qualify(): void
    {
        $this->updateStage('qualified');
        $this->incrementScore(20);
    }

    /**
     * Convert to invoiced.
     */
    public function convertToInvoiced(): void
    {
        $this->updateStage('invoiced');
        $this->incrementScore(30);
    }

    /**
     * Mark as paid.
     */
    public function markPaid(): void
    {
        $this->updateStage('paid');
        $this->updateScore(100);
    }

    /**
     * Mark as lost.
     */
    public function markLost(): void
    {
        $this->updateStage('lost');
    }
}
