<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Payment $payment,
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
            if ($preferences->email_payment_received) {
                $channels[] = 'mail';
            }

            if ($preferences->canSendSms('payment_received')) {
                $channels[] = SmsChannel::class;
            }

            if ($preferences->canSendWhatsApp('payment_received')) {
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
        // Load invoice with relationships for PDF generation
        $this->invoice->load(['businessProfile', 'customer', 'items']);

        // Get business profile (with fallback to first profile)
        $businessProfile = $this->invoice->businessProfile ?? $this->invoice->user->businessProfiles->first();

        // Generate PDF in memory
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $this->invoice,
            'businessProfile' => $businessProfile,
        ]);

        $pdfContent = $pdf->output();
        $pdfFilename = 'Invoice-' . $this->invoice->invoice_number . '-PAID.pdf';

        return (new MailMessage)
            ->subject('Payment Received - ' . $this->invoice->invoice_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We have received your payment of ' . $this->formatAmount() . ' for invoice ' . $this->invoice->invoice_number . '.')
            ->line('Payment Method: ' . ucfirst($this->payment->payment_method))
            ->line('Reference: ' . $this->payment->reference_number)
            ->line('Thank you for your prompt payment!')
            ->line('A copy of the paid invoice is attached to this email for your records.')
            ->action('View Invoice', url('/app/invoices/' . $this->invoice->id))
            ->attachData($pdfContent, $pdfFilename, ['mime' => 'application/pdf'])
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return sprintf(
            'Payment of %s received for invoice %s. Ref: %s. Thank you! - %s',
            $this->formatAmount(),
            $this->invoice->invoice_number,
            $this->payment->reference_number,
            config('app.name')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        return sprintf(
            "✅ *Payment Received*\n\nAmount: %s\nInvoice: %s\nReference: %s\n\nThank you for your payment!\n\n- %s",
            $this->formatAmount(),
            $this->invoice->invoice_number,
            $this->payment->reference_number,
            config('app.name')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'reference_number' => $this->payment->reference_number,
            'message' => 'Payment of ' . $this->formatAmount() . ' received for invoice ' . $this->invoice->invoice_number,
        ];
    }

    /**
     * Format payment amount with currency.
     */
    protected function formatAmount(): string
    {
        return $this->invoice->currency . ' ' . number_format($this->payment->amount, 2);
    }
}
