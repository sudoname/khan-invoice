<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSentNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice
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
            if ($preferences->email_invoice_sent) {
                $channels[] = 'mail';
            }

            if ($preferences->canSendSms('invoice_sent')) {
                $channels[] = SmsChannel::class;
            }

            if ($preferences->canSendWhatsApp('invoice_sent')) {
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
        $publicInvoiceUrl = route('public-invoice.show', $this->invoice->public_id);
        $payNowUrl = route('public-invoice.pay', $this->invoice->public_id);

        return (new MailMessage)
            ->subject('New Invoice - ' . $this->invoice->invoice_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new invoice from ' . ($this->invoice->businessProfile->business_name ?? config('app.name')) . '.')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: ' . $this->formatAmount())
            ->line('Due Date: ' . $dueDate)
            ->action('Pay Now', $payNowUrl)
            ->line('Or view invoice: ' . $publicInvoiceUrl)
            ->line('Please ensure payment is made before the due date.')
            ->salutation('Best regards, ' . ($this->invoice->businessProfile->business_name ?? config('app.name')));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $publicInvoiceUrl = route('public-invoice.show', $this->invoice->public_id);

        return sprintf(
            'Invoice %s for %s is due on %s. View & Pay: %s',
            $this->invoice->invoice_number,
            $this->formatAmount(),
            $this->invoice->due_date->format('M d'),
            $publicInvoiceUrl
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $publicInvoiceUrl = route('public-invoice.show', $this->invoice->public_id);

        return sprintf(
            "📄 *New Invoice*\n\nInvoice: %s\nAmount: %s\nDue Date: %s\n\nView & Pay Now:\n%s\n\nPlease ensure payment is made before the due date.\n\n- %s",
            $this->invoice->invoice_number,
            $this->formatAmount(),
            $this->invoice->due_date->format('M d, Y'),
            $publicInvoiceUrl,
            $this->invoice->businessProfile->business_name ?? config('app.name')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_sent',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date->toDateString(),
            'message' => 'Invoice ' . $this->invoice->invoice_number . ' for ' . $this->formatAmount() . ' has been sent',
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
