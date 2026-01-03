<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

class LogFailedEmail
{
    /**
     * Handle the event - logs emails that fail to send.
     */
    public function failed(MessageSending $event, \Throwable $exception): void
    {
        try {
            $message = $event->message;

            // Get recipient info
            $recipients = $message->getTo();
            $recipientEmail = array_key_first($recipients);
            $recipientName = $recipients[$recipientEmail] ?? null;

            // Get subject
            $subject = $message->getSubject();

            // Determine message type
            $messageType = $this->determineMessageType($subject, $event->data);

            // Try to get user_id
            $userId = $this->getUserId($event->data, $recipientEmail);

            // Create email log with failed status
            EmailLog::create([
                'user_id' => $userId,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'message_type' => $messageType,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'provider' => config('mail.default'),
                'sent_at' => null,
            ]);

            Log::error('Email sending failed and logged', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

        } catch (\Exception $e) {
            // Don't let logging failures cause more issues
            Log::error('Failed to log failed email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine message type from subject line or notification data.
     */
    protected function determineMessageType(string $subject, array $data): string
    {
        // Check notification class name if available
        if (isset($data['__laravel_notification'])) {
            $notificationClass = $data['__laravel_notification'];

            if (str_contains($notificationClass, 'InvoiceSent')) {
                return 'invoice_sent';
            }
            if (str_contains($notificationClass, 'PaymentReceived')) {
                return 'payment_received';
            }
            if (str_contains($notificationClass, 'PaymentReminder')) {
                return 'payment_reminder';
            }
            if (str_contains($notificationClass, 'InvoiceOverdue')) {
                return 'invoice_overdue';
            }
            if (str_contains($notificationClass, 'VerifyEmail')) {
                return 'verification';
            }
            if (str_contains($notificationClass, 'WelcomeNotification')) {
                return 'welcome';
            }
            if (str_contains($notificationClass, 'SubscriptionChanged')) {
                return 'subscription_changed';
            }
        }

        // Fallback: Analyze subject line
        $subjectLower = strtolower($subject);

        if (str_contains($subjectLower, 'verify') || str_contains($subjectLower, 'verification')) {
            return 'verification';
        }
        if (str_contains($subjectLower, 'invoice') && str_contains($subjectLower, 'sent')) {
            return 'invoice_sent';
        }
        if (str_contains($subjectLower, 'payment') && str_contains($subjectLower, 'received')) {
            return 'payment_received';
        }
        if (str_contains($subjectLower, 'reminder')) {
            return 'payment_reminder';
        }
        if (str_contains($subjectLower, 'overdue')) {
            return 'invoice_overdue';
        }

        return 'general';
    }

    /**
     * Extract user ID from notification data or recipient email.
     */
    protected function getUserId(array $data, string $recipientEmail): ?int
    {
        // Check if user ID is in notification data
        if (isset($data['user_id'])) {
            return $data['user_id'];
        }

        // Try to find user by email
        $user = User::where('email', $recipientEmail)->first();

        return $user?->id;
    }
}
