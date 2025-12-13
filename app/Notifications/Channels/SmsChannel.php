<?php

namespace App\Notifications\Channels;

use App\Models\SmsLog;
use App\Services\TermiiService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct(
        protected TermiiService $termiiService
    ) {
        //
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Check if notification has toSms method
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        // Get the customer's phone number
        $phoneNumber = $this->getPhoneNumber($notifiable);

        if (!$phoneNumber) {
            Log::warning('SMS notification skipped: No phone number found', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'notification_type' => get_class($notification),
            ]);
            return;
        }

        // Get user from notifiable (could be customer or user)
        $user = $this->getUser($notifiable);

        if (!$user) {
            Log::warning('SMS notification skipped: Could not determine user', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
            ]);
            return;
        }

        // Get SMS message
        $message = $notification->toSms($notifiable);

        // Determine message type from notification class
        $messageType = $this->getMessageType($notification);

        // Send SMS via Termii
        $result = $this->termiiService->sendSms($phoneNumber, $message);

        // Create SMS log entry
        $logData = [
            'user_id' => $user->id,
            'recipient_phone' => $phoneNumber,
            'message_type' => $messageType,
            'message_content' => $message,
            'status' => $result['status'] ? 'sent' : 'failed',
            'provider_message_id' => $result['data']['message_id'] ?? null,
            'error_message' => $result['status'] ? null : $result['message'],
            'cost' => 1.0, // Base cost per SMS
        ];

        SmsLog::create($logData);

        // Deduct credit if successful
        if ($result['status'] && $user->notificationPreferences) {
            $user->notificationPreferences->deductSmsCredit();
        }

        // Log result
        if ($result['status']) {
            Log::info('SMS notification sent successfully', [
                'recipient' => $phoneNumber,
                'message_type' => $messageType,
                'message_id' => $result['data']['message_id'] ?? null,
            ]);
        } else {
            Log::error('SMS notification failed', [
                'recipient' => $phoneNumber,
                'message_type' => $messageType,
                'error' => $result['message'],
            ]);
        }
    }

    /**
     * Get phone number from notifiable.
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        // Try to get phone from notifiable directly
        if (isset($notifiable->phone) && !empty($notifiable->phone)) {
            return $notifiable->phone;
        }

        // If notifiable is a user, check their profile
        if (method_exists($notifiable, 'businessProfile') && $notifiable->businessProfile) {
            return $notifiable->businessProfile->phone ?? null;
        }

        return null;
    }

    /**
     * Get user from notifiable.
     */
    protected function getUser(object $notifiable): ?object
    {
        // If notifiable is a User
        if (get_class($notifiable) === 'App\Models\User') {
            return $notifiable;
        }

        // If notifiable is a Customer, get their user
        if (method_exists($notifiable, 'user')) {
            return $notifiable->user;
        }

        return null;
    }

    /**
     * Determine message type from notification class name.
     */
    protected function getMessageType(Notification $notification): string
    {
        $className = class_basename($notification);

        return match ($className) {
            'PaymentReceivedNotification' => 'payment_received',
            'InvoiceSentNotification' => 'invoice_sent',
            'PaymentReminderNotification' => 'payment_reminder',
            'InvoiceOverdueNotification' => 'invoice_overdue',
            default => 'general',
        };
    }
}
