<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationQueue extends Model
{
    protected $table = 'generation_queue';

    protected $fillable = [
        'design_id',
        'priority',
        'status',
        'attempts',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the marketing design for this queue entry
     */
    public function design(): BelongsTo
    {
        return $this->belongsTo(MarketingDesign::class, 'design_id');
    }

    /**
     * Check if job is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if job is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if job is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if job has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark job as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Increment attempts counter
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Check if max attempts exceeded
     */
    public function maxAttemptsExceeded(int $maxAttempts = 3): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    /**
     * Scope for pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Scope for next job in queue
     */
    public function scopeNextInQueue($query)
    {
        return $query->pending()
            ->byPriority()
            ->first();
    }
}
