<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaystackService $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Initiate payment for an invoice
     */
    public function initiate(Request $request, string $publicId)
    {
        try {
            $invoice = Invoice::where('public_id', $publicId)
                ->with(['customer', 'businessProfile'])
                ->firstOrFail();

            // Check if invoice is already paid or cancelled
            if (in_array($invoice->status, ['paid', 'cancelled'])) {
                return redirect()
                    ->route('invoice.public', $publicId)
                    ->with('error', 'This invoice cannot be paid.');
            }

            // Calculate balance due
            $balanceDue = $invoice->total_amount - $invoice->amount_paid;

            if ($balanceDue <= 0) {
                return redirect()
                    ->route('invoice.public', $publicId)
                    ->with('error', 'This invoice has already been paid in full.');
            }

            // Generate unique payment reference
            $reference = PaystackService::generateReference();

            // Store reference temporarily in the invoice
            $invoice->update([
                'payment_reference' => $reference,
                'payment_status' => 'processing',
                'payment_gateway' => 'paystack',
            ]);

            // Prepare transaction data
            $transactionData = [
                'email' => $invoice->customer->email,
                'amount' => $balanceDue,
                'reference' => $reference,
                'callback_url' => route('payment.callback'),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name,
                    'business_name' => $invoice->businessProfile->business_name ?? '',
                ],
            ];

            // Add subaccount if business has one configured
            if ($invoice->businessProfile && $invoice->businessProfile->paystack_subaccount_code) {
                $transactionData['subaccount'] = $invoice->businessProfile->paystack_subaccount_code;
            }

            // Initialize Paystack transaction
            $result = $this->paystackService->initializeTransaction($transactionData);

            if ($result['status']) {
                // Redirect to Paystack payment page
                return redirect($result['data']['authorization_url']);
            }

            // Reset payment status if initialization failed
            $invoice->update(['payment_status' => 'failed']);

            return redirect()
                ->route('invoice.public', $publicId)
                ->with('error', $result['message'] ?? 'Failed to initialize payment. Please try again.');

        } catch (\Exception $e) {
            Log::error('Payment initiation error: ' . $e->getMessage());

            return redirect()
                ->route('invoice.public', $publicId)
                ->with('error', 'An error occurred. Please try again later.');
        }
    }

    /**
     * Handle payment callback from Paystack
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect('/')->with('error', 'Invalid payment reference.');
        }

        try {
            // Verify transaction with Paystack
            $result = $this->paystackService->verifyTransaction($reference);

            if (!$result['status']) {
                $invoice = Invoice::where('payment_reference', $reference)->first();

                if ($invoice) {
                    $invoice->update(['payment_status' => 'failed']);

                    return redirect()
                        ->route('invoice.public', $invoice->public_id)
                        ->with('error', 'Payment verification failed. Please try again.');
                }

                return redirect('/')->with('error', 'Payment verification failed.');
            }

            $data = $result['data'];

            // Find invoice by reference
            $invoice = Invoice::where('payment_reference', $reference)->firstOrFail();

            // Check if payment was successful
            if ($data['status'] === 'success') {
                $amountPaid = PaystackService::toNaira($data['amount']);

                // Check if payment record already exists to avoid duplicates
                $existingPayment = Payment::where('reference_number', $reference)
                    ->where('invoice_id', $invoice->id)
                    ->first();

                if (!$existingPayment) {
                    // Create payment record
                    $payment = Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $amountPaid,
                        'payment_date' => now(),
                        'payment_method' => 'paystack',
                        'reference_number' => $reference,
                        'notes' => 'Payment via Paystack - Transaction ID: ' . ($data['id'] ?? $reference),
                    ]);

                    // Update invoice payment details
                    $invoice->update([
                        'amount_paid' => $invoice->amount_paid + $amountPaid,
                        'payment_status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    // Update invoice status
                    if ($invoice->amount_paid >= $invoice->total_amount) {
                        $invoice->update(['status' => 'paid']);
                    } else {
                        $invoice->update(['status' => 'partially_paid']);
                    }

                    // Reload invoice with relationships
                    $invoice->load('customer');

                    // Send payment received notification to customer
                    try {
                        $invoice->customer->notify(new PaymentReceivedNotification($payment, $invoice));
                        Log::info('Payment received notification queued for customer', [
                            'invoice_id' => $invoice->id,
                            'customer_id' => $invoice->customer->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send payment notification: ' . $e->getMessage());
                    }
                }

                return redirect()
                    ->route('invoice.public', $invoice->public_id)
                    ->with('success', 'Payment successful! Thank you for your payment.');
            }

            // Payment was not successful
            $invoice->update(['payment_status' => 'failed']);

            return redirect()
                ->route('invoice.public', $invoice->public_id)
                ->with('error', 'Payment was not successful. Please try again.');

        } catch (\Exception $e) {
            Log::error('Payment callback error: ' . $e->getMessage());

            return redirect('/')
                ->with('error', 'An error occurred while processing your payment.');
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function webhook(Request $request)
    {
        // Verify Paystack signature
        $signature = $request->header('x-paystack-signature');

        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), config('services.paystack.secret_key'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        try {
            if ($event === 'charge.success') {
                $reference = $data['reference'];
                $invoice = Invoice::where('payment_reference', $reference)->first();

                if ($invoice) {
                    $amountPaid = PaystackService::toNaira($data['amount']);

                    // Check if payment record already exists to avoid duplicates
                    $existingPayment = Payment::where('reference_number', $reference)
                        ->where('invoice_id', $invoice->id)
                        ->first();

                    if (!$existingPayment) {
                        // Create payment record
                        $payment = Payment::create([
                            'invoice_id' => $invoice->id,
                            'amount' => $amountPaid,
                            'payment_date' => now(),
                            'payment_method' => 'paystack',
                            'reference_number' => $reference,
                            'notes' => 'Payment via Paystack Webhook - Transaction ID: ' . ($data['id'] ?? $reference),
                        ]);

                        // Update invoice payment details
                        $invoice->update([
                            'amount_paid' => $invoice->amount_paid + $amountPaid,
                            'payment_status' => 'completed',
                            'paid_at' => now(),
                        ]);

                        // Update invoice status
                        if ($invoice->amount_paid >= $invoice->total_amount) {
                            $invoice->update(['status' => 'paid']);
                        } else {
                            $invoice->update(['status' => 'partially_paid']);
                        }

                        // Reload invoice with relationships
                        $invoice->load('customer');

                        // Send payment received notification to customer
                        try {
                            $invoice->customer->notify(new PaymentReceivedNotification($payment, $invoice));
                            Log::info('Payment received notification queued for customer via webhook', [
                                'invoice_id' => $invoice->id,
                                'customer_id' => $invoice->customer->id,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send payment notification via webhook: ' . $e->getMessage());
                        }
                    }

                    Log::info('Webhook processed successfully for invoice: ' . $invoice->invoice_number);
                }
            }

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }
}
