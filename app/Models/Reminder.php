<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'invoice_id',
        'reminder_type',
        'days_offset',
        'send_email',
        'send_sms',
        'send_whatsapp',
        'scheduled_at',
        'status',
        'sent_at',
        'failed_at',
        'failure_reason',
        'delivery_metadata',
        'custom_message',
        'include_payment_link',
        'skip_reason',
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'send_sms' => 'boolean',
        'send_whatsapp' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'delivery_metadata' => 'array',
        'include_payment_link' => 'boolean',
    ];

    /**
     * Get the invoice for this reminder
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Check if reminder is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'SCHEDULED';
    }

    /**
     * Check if reminder was sent
     */
    public function wasSent(): bool
    {
        return $this->status === 'SENT';
    }

    /**
     * Check if reminder should be sent now
     */
    public function shouldSendNow(): bool
    {
        return $this->isScheduled() && $this->scheduled_at <= now();
    }

    /**
     * Mark reminder as sent
     */
    public function markAsSent(array $metadata = []): void
    {
        $this->update([
            'status' => 'SENT',
            'sent_at' => now(),
            'delivery_metadata' => $metadata,
        ]);
    }

    /**
     * Mark reminder as failed
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'FAILED',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Skip reminder with reason
     */
    public function skip(string $reason): void
    {
        $this->update([
            'status' => 'SKIPPED',
            'skip_reason' => $reason,
        ]);
    }

    /**
     * Get active channels for this reminder
     */
    public function getActiveChannels(): array
    {
        $channels = [];

        if ($this->send_email) {
            $channels[] = 'email';
        }

        if ($this->send_sms) {
            $channels[] = 'sms';
        }

        if ($this->send_whatsapp) {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }
}
