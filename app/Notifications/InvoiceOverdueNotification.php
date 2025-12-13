<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue
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
            if ($preferences->email_invoice_overdue) {
                $channels[] = 'mail';
            }

            if ($preferences->canSendSms('invoice_overdue')) {
                $channels[] = SmsChannel::class;
            }

            if ($preferences->canSendWhatsApp('invoice_overdue')) {
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

        return (new MailMessage)
            ->subject('OVERDUE: Invoice ' . $this->invoice->invoice_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is an important notice regarding your overdue invoice.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: ' . $this->formatAmount())
            ->line('Original Due Date: ' . $dueDate)
            ->line('Days Overdue: ' . $this->daysOverdue . ' days')
            ->line('Please make payment immediately to avoid late fees or service interruption.')
            ->action('Pay Now', url('/app/invoices/' . $this->invoice->id))
            ->line('If you have any questions or need to discuss payment arrangements, please contact us.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return sprintf(
            'URGENT: Invoice %s is OVERDUE by %d days. Amount: %s. Please pay immediately. - %s',
            $this->invoice->invoice_number,
            $this->daysOverdue,
            $this->formatAmount(),
            config('app.name')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "❗ *INVOICE OVERDUE*\n\n🚨 Urgent Payment Required\n\nInvoice: %s\nAmount: %s\nDue Date: %s\nDays Overdue: *%d day%s*\n\nPlease make payment immediately to avoid late fees.\n\nIf you have questions, please contact us.\n\n- %s",
            $this->invoice->invoice_number,
            $this->formatAmount(),
            $this->invoice->due_date->format('M d, Y'),
            $this->daysOverdue,
            $this->daysOverdue === 1 ? '' : 's',
            config('app.name')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_overdue',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date->toDateString(),
            'days_overdue' => $this->daysOverdue,
            'message' => 'Invoice ' . $this->invoice->invoice_number . ' is overdue by ' . $this->daysOverdue . ' days',
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
