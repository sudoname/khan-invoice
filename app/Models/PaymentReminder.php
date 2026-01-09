<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'invoice_id',
        'channel',
        'scheduled_at',
        'status',
        'message',
        'recipient',
        'reference',
        'sent_at',
        'last_error',
        'retry_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Get the invoice that this reminder belongs to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Check if reminder is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if reminder was sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if reminder failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if reminder is scheduled for future
     */
    public function isScheduledForFuture(): bool
    {
        return $this->scheduled_at->isFuture();
    }

    /**
     * Check if reminder is overdue (scheduled but not sent)
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->scheduled_at->isPast();
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark reminder as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Cancel reminder
     */
    public function cancel(): void
    {
        $this->update(['status' => 'canceled']);
    }

    /**
     * Generate unique reference for tracking
     */
    public static function generateReference(): string
    {
        return 'REM-' . strtoupper(uniqid());
    }

    /**
     * Scope: Only pending reminders
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Only sent reminders
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Only failed reminders
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Reminders scheduled for a specific date
     */
    public function scopeScheduledFor($query, $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }

    /**
     * Scope: Overdue reminders (pending and scheduled in past)
     */
    public function scopeOverdue($query)
    {
        return $query->pending()
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope: Reminders for a specific invoice
     */
    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: Reminders by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
