<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment);
    }

    /**
     * Update invoice amount_paid and status based on payments
     */
    protected function updateInvoiceStatus(Payment $payment): void
    {
        $invoice = $payment->invoice;

        if (!$invoice) {
            return;
        }

        // Calculate total amount paid from all payments
        $totalPaid = $invoice->payments()->sum('amount');

        // Update invoice amount_paid
        $invoice->amount_paid = $totalPaid;

        // Determine invoice status based on payment
        if ($totalPaid >= $invoice->total_amount) {
            // Fully paid
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0 && $totalPaid < $invoice->total_amount) {
            // Partially paid
            $invoice->status = 'partially_paid';
        } elseif ($totalPaid == 0) {
            // No payments - check if overdue
            if ($invoice->due_date < now() && in_array($invoice->status, ['sent', 'partially_paid'])) {
                $invoice->status = 'overdue';
            } elseif ($invoice->status == 'partially_paid') {
                // Was partially paid but payment was removed
                $invoice->status = 'sent';
            }
        }

        // Save without triggering events to prevent infinite loop
        $invoice->saveQuietly();
    }
}
