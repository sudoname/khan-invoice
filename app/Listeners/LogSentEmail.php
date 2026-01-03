<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentEmail
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->sent->getOriginalMessage();

            // Get recipient info
            $recipients = $message->getTo();
            $recipientEmail = array_key_first($recipients);
            $recipientName = $recipients[$recipientEmail] ?? null;

            // Get subject
            $subject = $message->getSubject();

            // Get body excerpt (first 500 chars, strip HTML)
            $body = $message->getBody();
            $bodyExcerpt = null;

            if ($body) {
                // Handle different body types
                if (is_string($body)) {
                    $bodyText = strip_tags($body);
                    $bodyExcerpt = mb_substr($bodyText, 0, 500);
                } elseif (method_exists($body, 'bodyToString')) {
                    $bodyText = strip_tags($body->bodyToString());
                    $bodyExcerpt = mb_substr($bodyText, 0, 500);
                } elseif (method_exists($body, 'toString')) {
                    $bodyText = strip_tags($body->toString());
                    $bodyExcerpt = mb_substr($bodyText, 0, 500);
                }
            }

            // Determine message type from subject or notification data
            $messageType = $this->determineMessageType($subject, $event->data);

            // Try to get user_id from notification data or recipient email
            $userId = $this->getUserId($event->data, $recipientEmail);

            // Extract metadata from notification
            $metadata = $this->extractMetadata($event->data);

            // Get message ID from headers if available
            $messageId = $message->getId();

            // Create email log
            EmailLog::create([
                'user_id' => $userId,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'message_type' => $messageType,
                'body_excerpt' => $bodyExcerpt,
                'status' => 'sent',
                'provider' => config('mail.default'),
                'message_id' => $messageId,
                'metadata' => $metadata,
                'sent_at' => now(),
            ]);

            Log::info('Email logged successfully', [
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'message_type' => $messageType,
            ]);

        } catch (\Exception $e) {
            // Don't let logging failures affect email sending
            Log::error('Failed to log email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        if (str_contains($subjectLower, 'welcome')) {
            return 'welcome';
        }
        if (str_contains($subjectLower, 'reset') || str_contains($subjectLower, 'password')) {
            return 'password_reset';
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

    /**
     * Extract relevant metadata from notification data.
     */
    protected function extractMetadata(array $data): array
    {
        $metadata = [];

        // Extract common fields
        $fields = [
            'invoice_id',
            'invoice_number',
            'customer_id',
            'amount',
            'due_date',
            'days_until_due',
            'days_overdue',
        ];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $metadata[$field] = $data[$field];
            }
        }

        return $metadata;
    }
}
