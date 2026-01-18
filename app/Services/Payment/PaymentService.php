<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\FeatureFlag;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\InvoicePayment;
use App\Models\Payment\MerchantAccount;
use App\Models\Payment\LedgerEntry;
use App\Services\Payment\Providers\ProviderFactory;
use App\Services\Payment\Providers\PaymentProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected PaymentProviderInterface $provider;

    public function __construct()
    {
        $this->provider = ProviderFactory::getDefault();
    }

    /**
     * Check if payment orchestration is enabled
     */
    public function isEnabled(): bool
    {
        return FeatureFlag::isEnabledForEnvironment('payment_orchestration');
    }

    /**
     * Initialize a payment for an invoice
     */
    public function initializePayment(Invoice $invoice, array $options = []): array
    {
        try {
            if (!$invoice->payment_enabled) {
                return [
                    'success' => false,
                    'message' => 'Payment is not enabled for this invoice',
                ];
            }

            if ($invoice->payment_expires_at && $invoice->payment_expires_at < now()) {
                return [
                    'success' => false,
                    'message' => 'Payment link has expired',
                ];
            }

            $reference = $options['reference'] ?? $this->generateReference();

            $paymentAttempt = PaymentAttempt::create([
                'invoice_id' => $invoice->id,
                'provider' => $this->provider->getProviderName(),
                'reference' => $reference,
                'status' => 'INITIATED',
                'amount' => $options['amount'] ?? $invoice->amount_due,
                'currency' => $invoice->currency ?? 'NGN',
                'customer_email' => $options['email'] ?? $invoice->customer_email,
                'customer_phone' => $options['phone'] ?? $invoice->customer_phone,
                'customer_name' => $options['name'] ?? $invoice->customer_name,
                'initiated_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            $result = $this->provider->initializePayment([
                'email' => $paymentAttempt->customer_email,
                'amount' => $paymentAttempt->amount,
                'reference' => $reference,
                'callback_url' => $options['callback_url'] ?? route('payment.callback', $invoice->uuid),
                'metadata' => array_merge([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'payment_attempt_id' => $paymentAttempt->id,
                ], $options['metadata'] ?? []),
                'subaccount' => $options['subaccount'] ?? null,
            ]);

            if ($result->isSuccessful()) {
                $paymentAttempt->update([
                    'authorization_url' => $result->authorizationUrl,
                    'status' => 'PENDING',
                    'metadata' => $result->metadata,
                ]);

                return [
                    'success' => true,
                    'payment_attempt_id' => $paymentAttempt->id,
                    'authorization_url' => $result->authorizationUrl,
                    'reference' => $reference,
                ];
            }

            $paymentAttempt->markAsFailed($result->errorMessage ?? 'Unknown error');

            return [
                'success' => false,
                'message' => $result->errorMessage ?? 'Payment initialization failed',
            ];

        } catch (\Exception $e) {
            Log::error('PaymentService::initializePayment failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while initializing payment',
            ];
        }
    }

    /**
     * Verify and process a payment
     */
    public function verifyPayment(string $reference): array
    {
        try {
            $paymentAttempt = PaymentAttempt::where('reference', $reference)->firstOrFail();

            if ($paymentAttempt->isSuccessful()) {
                return [
                    'success' => true,
                    'message' => 'Payment already verified',
                    'payment_attempt' => $paymentAttempt,
                ];
            }

            $result = $this->provider->verifyPayment($reference);

            if ($result->isSuccessful()) {
                return DB::transaction(function () use ($paymentAttempt, $result) {
                    $paymentAttempt->markAsSuccessful([
                        'fees' => $result->fees,
                        'net_amount' => $result->getNetAmount(),
                        'metadata' => $result->metadata,
                    ]);

                    $invoicePayment = $this->createInvoicePayment($paymentAttempt, $result);

                    $this->reconcileInvoice($paymentAttempt->invoice, $invoicePayment);

                    // Credit merchant's ledger with payment received
                    $this->recordPaymentInLedger($paymentAttempt->invoice, $invoicePayment, $result);

                    return [
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'payment_attempt' => $paymentAttempt->fresh(),
                        'invoice_payment' => $invoicePayment,
                    ];
                });
            }

            $paymentAttempt->markAsFailed($result->errorMessage ?? 'Payment verification failed');

            return [
                'success' => false,
                'message' => $result->errorMessage ?? 'Payment verification failed',
                'payment_attempt' => $paymentAttempt,
            ];

        } catch (\Exception $e) {
            Log::error('PaymentService::verifyPayment failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while verifying payment',
            ];
        }
    }

    /**
     * Create invoice payment record
     */
    protected function createInvoicePayment(PaymentAttempt $attempt, $verificationResult): InvoicePayment
    {
        return InvoicePayment::create([
            'invoice_id' => $attempt->invoice_id,
            'payment_attempt_id' => $attempt->id,
            'amount_paid' => $verificationResult->amount,
            'fees_paid' => $verificationResult->fees,
            'net_received' => $verificationResult->getNetAmount(),
            'currency' => $verificationResult->currency,
            'payment_method' => $verificationResult->channel,
            'payment_reference' => $verificationResult->reference,
            'payment_metadata' => $verificationResult->metadata,
            'reconciliation_status' => 'PENDING',
            'paid_at' => $verificationResult->paidAt,
        ]);
    }

    /**
     * Reconcile invoice with payment
     */
    protected function reconcileInvoice(Invoice $invoice, InvoicePayment $payment): void
    {
        $amountPaid = $invoice->amount_paid ?? 0;
        $newAmountPaid = $amountPaid + $payment->amount_paid;
        $amountDue = $invoice->amount_due - $payment->amount_paid;

        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'amount_due' => max(0, $amountDue),
            'payment_status' => $amountDue <= 0 ? 'completed' : 'processing',
            'last_payment_at' => now(),
            'paid_at' => $amountDue <= 0 ? now() : $invoice->paid_at,
        ]);

        $payment->markAsReconciled();
    }

    /**
     * Record payment in merchant's ledger
     */
    protected function recordPaymentInLedger(Invoice $invoice, InvoicePayment $invoicePayment, $verificationResult): void
    {
        // Check if ledger entry already exists for this payment to prevent duplicates
        $existingEntry = LedgerEntry::where('invoice_payment_id', $invoicePayment->id)
            ->where('entry_type', 'PAYMENT_RECEIVED')
            ->first();

        if ($existingEntry) {
            Log::info('Ledger entry already exists for this payment, skipping', [
                'invoice_payment_id' => $invoicePayment->id,
                'invoice_id' => $invoice->id,
                'existing_entry_id' => $existingEntry->id,
            ]);
            return;
        }

        // Get merchant's current balance
        $merchantAccount = MerchantAccount::where('user_id', $invoice->user_id)
            ->where('is_primary', true)
            ->first();

        if (!$merchantAccount) {
            Log::warning('No primary merchant account found for payment', [
                'user_id' => $invoice->user_id,
                'invoice_id' => $invoice->id,
                'payment_amount' => $invoicePayment->net_received,
            ]);
            return;
        }

        $currentBalance = $merchantAccount->getAvailableBalance();
        $netReceived = (float) $invoicePayment->net_received;
        $balanceAfter = $currentBalance + $netReceived;

        // Create ledger entry for payment received
        LedgerEntry::create([
            'user_id' => $invoice->user_id,
            'entry_type' => 'PAYMENT_RECEIVED',
            'account_type' => 'CREDIT',
            'amount' => $netReceived,
            'balance_after' => $balanceAfter,
            'currency' => $invoicePayment->currency ?? 'NGN',
            'invoice_payment_id' => $invoicePayment->id,
            'invoice_id' => $invoice->id,
            'description' => "Payment received for invoice {$invoice->invoice_number}",
            'reference' => LedgerEntry::generateReference('PAYMENT'),
            'entry_date' => now(),
        ]);

        // Record gateway fees if any
        if ($verificationResult->fees > 0) {
            $balanceAfterFees = $balanceAfter; // Fees already deducted in net_received
            LedgerEntry::create([
                'user_id' => $invoice->user_id,
                'entry_type' => 'GATEWAY_FEE',
                'account_type' => 'DEBIT',
                'amount' => $verificationResult->fees,
                'balance_after' => $balanceAfterFees,
                'currency' => $invoicePayment->currency ?? 'NGN',
                'invoice_payment_id' => $invoicePayment->id,
                'invoice_id' => $invoice->id,
                'description' => "Gateway fees for invoice {$invoice->invoice_number}",
                'reference' => LedgerEntry::generateReference('GATEWAY_FEE'),
                'entry_date' => now(),
            ]);
        }

        Log::info('Payment recorded in ledger', [
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'net_received' => $netReceived,
            'fees' => $verificationResult->fees,
            'new_balance' => $balanceAfter,
        ]);
    }

    /**
     * Generate unique payment reference
     */
    protected function generateReference(): string
    {
        return 'PAY_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Get payment provider
     */
    public function getProvider(): PaymentProviderInterface
    {
        return $this->provider;
    }

    /**
     * Set payment provider
     */
    public function setProvider(string $providerName): self
    {
        $this->provider = ProviderFactory::make($providerName);
        return $this;
    }
}
