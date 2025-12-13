<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public int $daysUntilDue
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $preferences = $notifiable->notificationPreferences;

        if ($preferences) {
            if ($preferences->email_payment_reminder) {
                $channels[] = 'mail';
            }

            if ($preferences->canSendSms('payment_reminder')) {
                $channels[] = SmsChannel::class;
            }

            if ($preferences->canSendWhatsApp('payment_reminder')) {
                $channels[] = WhatsAppChannel::class;
            }
        } else {
            // Default to email if no preferences set
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->invoice->due_date->format('M d, Y');
        $subject = $this->daysUntilDue === 0
            ? 'Payment Due Today - ' . $this->invoice->invoice_number
            : 'Payment Reminder - ' . $this->invoice->invoice_number;

        $urgencyMessage = $this->daysUntilDue === 0
            ? 'This invoice is due today. Please make payment as soon as possible.'
            : 'This invoice is due in ' . $this->daysUntilDue . ' days. Please ensure payment is made before the due date.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a friendly reminder about your upcoming payment.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: ' . $this->formatAmount())
            ->line('Due Date: ' . $dueDate)
            ->line($urgencyMessage)
            ->action('View Invoice', url('/app/invoices/' . $this->invoice->id))
            ->line('If you have already made payment, please disregard this message.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $urgency = $this->daysUntilDue === 0 ? 'DUE TODAY' : "Due in {$this->daysUntilDue} days";

        return sprintf(
            'REMINDER: Invoice %s (%s) - %s. Amount: %s. - %s',
            $this->invoice->invoice_number,
            $urgency,
            $this->invoice->due_date->format('M d'),
            $this->formatAmount(),
            config('app.name')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $urgencyIcon = $this->daysUntilDue === 0 ? '🚨' : '⏰';
        $urgencyText = $this->daysUntilDue === 0
            ? '*DUE TODAY*'
            : sprintf('*Due in %d day%s*', $this->daysUntilDue, $this->daysUntilDue === 1 ? '' : 's');

        return sprintf(
            "%s *Payment Reminder*\n\n%s\n\nInvoice: %s\nAmount: %s\nDue Date: %s\n\nPlease ensure payment is made before the due date.\n\n- %s",
            $urgencyIcon,
            $urgencyText,
            $this->invoice->invoice_number,
            $this->formatAmount(),
            $this->invoice->due_date->format('M d, Y'),
            config('app.name')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_reminder',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date->toDateString(),
            'days_until_due' => $this->daysUntilDue,
            'message' => 'Payment reminder: Invoice ' . $this->invoice->invoice_number . ' is due in ' . $this->daysUntilDue . ' days',
        ];
    }

    /**
     * Format invoice amount with currency.
     */
    protected function formatAmount(): string
    {
        return $this->invoice->currency . ' ' . number_format($this->invoice->total_amount, 2);
    }
}
