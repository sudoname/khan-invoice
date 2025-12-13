<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'sms_payment_received',
        'sms_invoice_sent',
        'sms_payment_reminder',
        'sms_invoice_overdue',
        'email_payment_received',
        'email_invoice_sent',
        'email_payment_reminder',
        'email_invoice_overdue',
        'sms_credits_remaining',
        'sms_enabled',
        'whatsapp_enabled',
        'whatsapp_credits_remaining',
        'whatsapp_payment_received',
        'whatsapp_invoice_sent',
        'whatsapp_payment_reminder',
        'whatsapp_invoice_overdue',
    ];

    protected $casts = [
        'sms_payment_received' => 'boolean',
        'sms_invoice_sent' => 'boolean',
        'sms_payment_reminder' => 'boolean',
        'sms_invoice_overdue' => 'boolean',
        'email_payment_received' => 'boolean',
        'email_invoice_sent' => 'boolean',
        'email_payment_reminder' => 'boolean',
        'email_invoice_overdue' => 'boolean',
        'sms_credits_remaining' => 'integer',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'whatsapp_credits_remaining' => 'integer',
        'whatsapp_payment_received' => 'boolean',
        'whatsapp_invoice_sent' => 'boolean',
        'whatsapp_payment_reminder' => 'boolean',
        'whatsapp_invoice_overdue' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if SMS can be sent for a given event type
     */
    public function canSendSms(string $eventType): bool
    {
        $preferenceKey = "sms_{$eventType}";
        return $this->sms_enabled
            && $this->sms_credits_remaining > 0
            && ($this->{$preferenceKey} ?? false);
    }

    /**
     * Deduct SMS credit after sending
     */
    public function deductSmsCredit(float $cost = 1.0): void
    {
        $this->decrement('sms_credits_remaining', 1);
    }

    /**
     * Check if WhatsApp can be sent for a given event type
     */
    public function canSendWhatsApp(string $eventType): bool
    {
        $preferenceKey = "whatsapp_{$eventType}";
        return $this->whatsapp_enabled
            && $this->whatsapp_credits_remaining > 0
            && ($this->{$preferenceKey} ?? false);
    }

    /**
     * Deduct WhatsApp credit after sending
     */
    public function deductWhatsAppCredit(float $cost = 1.0): void
    {
        $this->decrement('whatsapp_credits_remaining', 1);
    }
}
