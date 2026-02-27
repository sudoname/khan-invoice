<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Payment\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updateInvoiceStatus($payment);
        $this->ensureLedgerEntry($payment);
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

    /**
     * Ensure ledger entry exists for this payment.
     * This acts as a safety net for payments created outside the normal Paystack webhook flow.
     */
    protected function ensureLedgerEntry(Payment $payment): void
    {
        $invoice = $payment->invoice;

        if (!$invoice) {
            return;
        }

        // Check if ledger entry already exists for this invoice payment
        $existingEntry = LedgerEntry::where('invoice_id', $invoice->id)
            ->where('entry_type', 'PAYMENT_RECEIVED')
            ->where('reference', $payment->reference_number)
            ->exists();

        if ($existingEntry) {
            // Ledger entry already exists (created by proper payment flow)
            return;
        }

        // Only create ledger entries for supported payment methods
        if (!in_array($payment->payment_method, ['paystack', 'stripe', 'flutterwave'])) {
            return;
        }

        // Create ledger entries in a transaction
        DB::transaction(function () use ($payment, $invoice) {
            $user = $invoice->user;

            // Calculate fees based on payment method
            $fees = $this->calculatePaymentFees($payment->amount, $payment->payment_method);
            $netAmount = $payment->amount - $fees['gateway_fee'] - $fees['platform_fee'];

            // Get the last balance for this user
            $lastBalance = LedgerEntry::where('user_id', $user->id)
                ->orderBy('entry_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance_after') ?? 0;

            // Create PAYMENT_RECEIVED entry (CREDIT)
            $newBalance = $lastBalance + $netAmount;
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'PAYMENT_RECEIVED',
                'account_type' => 'CREDIT',
                'amount' => $netAmount,
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Payment received for invoice {$invoice->invoice_number}",
                'reference' => $payment->reference_number,
                'entry_date' => $payment->payment_date ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create GATEWAY_FEE entry (DEBIT)
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'GATEWAY_FEE',
                'account_type' => 'DEBIT',
                'amount' => $fees['gateway_fee'],
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Gateway fees for invoice {$invoice->invoice_number}",
                'reference' => $payment->reference_number . '_gateway_fee',
                'entry_date' => $payment->payment_date ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create PLATFORM_FEE entry (DEBIT)
            LedgerEntry::create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'entry_type' => 'PLATFORM_FEE',
                'account_type' => 'DEBIT',
                'amount' => $fees['platform_fee'],
                'balance_after' => $newBalance,
                'currency' => 'NGN',
                'description' => "Platform service charge for invoice {$invoice->invoice_number}",
                'reference' => $payment->reference_number . '_platform_fee',
                'entry_date' => $payment->payment_date ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Calculate payment gateway and platform fees
     */
    protected function calculatePaymentFees(float $amount, string $paymentMethod): array
    {
        $gatewayFee = 0;
        $platformFee = 0;

        switch ($paymentMethod) {
            case 'paystack':
                // Paystack Nigeria: 1.5% + ₦100 (capped at ₦2,000)
                $gatewayFee = min(($amount * 0.015) + 100, 2000);
                break;

            case 'stripe':
                // Stripe: 2.9% + $0.30 (example - adjust based on actual rates)
                $gatewayFee = ($amount * 0.029) + 0.30;
                break;

            case 'flutterwave':
                // Flutterwave: 1.4% (example - adjust based on actual rates)
                $gatewayFee = $amount * 0.014;
                break;
        }

        // Platform fee: 2% of gross amount
        $platformFee = $amount * 0.02;

        return [
            'gateway_fee' => round($gatewayFee, 2),
            'platform_fee' => round($platformFee, 2),
        ];
    }
}
