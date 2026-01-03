<?php

namespace App\Console\Commands;

use App\Models\Payment\PaymentAttempt;
use App\Services\Payment\PaymentService;
use Illuminate\Console\Command;

class VerifyPayment extends Command
{
    protected $signature = 'payment:verify {reference : Payment reference to verify}';
    protected $description = 'Verify a payment using its reference';

    public function handle()
    {
        $reference = $this->argument('reference');

        $this->info("🔍 Verifying payment: {$reference}");
        $this->newLine();

        try {
            // Check if payment attempt exists
            $attempt = PaymentAttempt::where('reference', $reference)->first();

            if (!$attempt) {
                $this->error('❌ Payment attempt not found');
                return 1;
            }

            $this->info('Payment Attempt Status: ' . $attempt->status);

            if ($attempt->isSuccessful()) {
                $this->info('✅ Payment already verified and processed');
                $this->displayPaymentDetails($attempt);
                return 0;
            }

            // Verify with provider
            $this->info('⏳ Verifying with payment provider...');

            $paymentService = app(PaymentService::class);
            $result = $paymentService->verifyPayment($reference);

            $this->newLine();

            if ($result['success']) {
                $this->info('✅ Payment verified successfully!');
                $this->newLine();

                $this->displayPaymentDetails($result['payment_attempt']);

                if (isset($result['invoice_payment'])) {
                    $this->newLine();
                    $this->info('💰 Invoice Payment Details:');
                    $payment = $result['invoice_payment'];
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Amount Paid', '₦' . number_format($payment->amount_paid, 2)],
                            ['Fees', '₦' . number_format($payment->fees_paid, 2)],
                            ['Net Received', '₦' . number_format($payment->net_received, 2)],
                            ['Payment Method', $payment->payment_method],
                            ['Reconciliation Status', $payment->reconciliation_status],
                        ]
                    );

                    // Show updated invoice
                    $invoice = $payment->invoice;
                    $this->newLine();
                    $this->info('📄 Invoice Status:');
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Invoice Number', $invoice->invoice_number],
                            ['Total Amount', '₦' . number_format($invoice->total_amount, 2)],
                            ['Amount Paid', '₦' . number_format($invoice->amount_paid, 2)],
                            ['Amount Due', '₦' . number_format($invoice->amount_due, 2)],
                            ['Payment Status', $invoice->payment_status],
                            ['Status', $invoice->status],
                        ]
                    );
                }

            } else {
                $this->error('❌ Payment verification failed: ' . $result['message']);
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    protected function displayPaymentDetails(PaymentAttempt $attempt)
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Reference', $attempt->reference],
                ['Status', $attempt->status],
                ['Provider', $attempt->provider],
                ['Channel', $attempt->channel ?? 'N/A'],
                ['Amount', '₦' . number_format($attempt->amount, 2)],
                ['Fees', '₦' . number_format($attempt->fees, 2)],
                ['Net Amount', '₦' . number_format($attempt->net_amount ?? 0, 2)],
                ['Customer Email', $attempt->customer_email],
                ['Initiated At', $attempt->initiated_at?->format('Y-m-d H:i:s')],
                ['Completed At', $attempt->completed_at?->format('Y-m-d H:i:s') ?? 'N/A'],
            ]
        );
    }
}
