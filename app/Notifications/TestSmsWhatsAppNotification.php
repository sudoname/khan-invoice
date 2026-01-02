<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Notification;

class TestSmsWhatsAppNotification extends Notification
{
    public function __construct(
        public string $phone,
        public string $channel = 'all'
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->channel === 'all' || $this->channel === 'sms') {
            $channels[] = SmsChannel::class;
        }

        if ($this->channel === 'all' || $this->channel === 'whatsapp') {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return sprintf(
            'TEST: This is a test SMS notification from %s. Your account is working correctly!',
            config('app.name')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "✅ *Test Notification*\n\nThis is a test WhatsApp message from %s.\n\nYour notification system is working correctly!\n\n- %s",
            config('app.name'),
            config('app.name')
        );
    }

    /**
     * Get phone number for SMS/WhatsApp.
     */
    public function getPhoneNumber(object $notifiable): ?string
    {
        return $this->phone;
    }
}
