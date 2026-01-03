<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PaymentEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'provider',
        'event_type',
        'reference',
        'event_id',
        'payload_hash',
        'payload_json',
        'status',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Check if event is duplicate
     */
    public static function isDuplicate(string $payloadHash): bool
    {
        return static::where('payload_hash', $payloadHash)->exists();
    }

    /**
     * Check if event has been processed
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['PROCESSED', 'DUPLICATE']);
    }

    /**
     * Mark event as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'PROCESSING']);
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'PROCESSED',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'FAILED',
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as duplicate
     */
    public function markAsDuplicate(): void
    {
        $this->update([
            'status' => 'DUPLICATE',
            'processed_at' => now(),
        ]);
    }
}
