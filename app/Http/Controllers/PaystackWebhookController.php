<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Payment\Payout;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private PaystackService $paystackService
    ) {}

    /**
     * Handle Paystack webhooks
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $input = $request->getContent();

        if (!$this->paystackService->verifyWebhookSignature($input, $signature)) {
            Log::warning('Invalid Paystack webhook signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        Log::info('Paystack webhook received', ['event' => $event]);

        // Handle different event types
        try {
            match($event) {
                'charge.success' => $this->handleChargeSuccess($data),
                'subscription.create' => $this->handleSubscriptionCreate($data),
                'subscription.disable' => $this->handleSubscriptionDisable($data),
                'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
                'invoice.create' => $this->handleInvoiceCreate($data),
                'invoice.update' => $this->handleInvoiceUpdate($data),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($data),
                'transfer.success' => $this->handleTransferSuccess($data),
                'transfer.failed' => $this->handleTransferFailed($data),
                'transfer.reversed' => $this->handleTransferReversed($data),
                default => Log::info('Unhandled Paystack webhook event', ['event' => $event]),
            };

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('Paystack webhook processing error', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful charge
     */
    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'];
        $customer = $data['customer'];

        // Find user by email
        $user = User::where('email', $customer['email'])->first();

        if (!$user) {
            Log::warning('User not found for charge success', ['email' => $customer['email']]);
            return;
        }

        // Record payment transaction
        PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $user->subscription?->id,
            'type' => 'subscription_payment',
            'status' => 'successful',
            'amount' => $data['amount'] / 100, // Convert from kobo
            'currency' => $data['currency'],
            'payment_gateway' => 'paystack',
            'transaction_reference' => $reference,
            'paystack_reference' => $data['reference'],
            'gateway_response' => json_encode($data),
            'description' => 'Subscription payment',
        ]);

        Log::info('Charge success recorded', ['reference' => $reference, 'user_id' => $user->id]);
    }

    /**
     * Handle subscription creation
     */
    private function handleSubscriptionCreate(array $data): void
    {
        $customer = $data['customer'];
        $user = User::where('email', $customer['email'])->first();

        if (!$user) {
            Log::warning('User not found for subscription create', ['email' => $customer['email']]);
            return;
        }

        // Update or create subscription
        Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'paystack_subscription_code' => $data['subscription_code'],
                'paystack_email_token' => $data['email_token'],
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => $data['next_payment_date'] ?? now()->addMonth(),
            ]
        );

        Log::info('Subscription created', ['subscription_code' => $data['subscription_code']]);
    }

    /**
     * Handle subscription disable
     */
    private function handleSubscriptionDisable(array $data): void
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if (!$subscription) {
            Log::warning('Subscription not found for disable', ['code' => $data['subscription_code']]);
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'expires_at' => $subscription->current_period_end ?? now(),
        ]);

        Log::info('Subscription disabled', ['subscription_id' => $subscription->id]);
    }

    /**
     * Handle subscription not renew
     */
    private function handleSubscriptionNotRenew(array $data): void
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'])->first();

        if (!$subscription) {
            Log::warning('Subscription not found for not renew', ['code' => $data['subscription_code']]);
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        Log::info('Subscription not renewing', ['subscription_id' => $subscription->id]);
    }

    /**
     * Handle invoice create
     */
    private function handleInvoiceCreate(array $data): void
    {
        Log::info('Paystack invoice created', ['invoice_code' => $data['invoice_code'] ?? 'unknown']);

        // You can send email notifications here
    }

    /**
     * Handle invoice update
     */
    private function handleInvoiceUpdate(array $data): void
    {
        Log::info('Paystack invoice updated', ['invoice_code' => $data['invoice_code'] ?? 'unknown']);
    }

    /**
     * Handle invoice payment failed
     */
    private function handleInvoicePaymentFailed(array $data): void
    {
        $subscription = Subscription::where('paystack_subscription_code', $data['subscription_code'] ?? null)->first();

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);

            Log::warning('Invoice payment failed', [
                'subscription_id' => $subscription->id,
                'invoice_code' => $data['invoice_code'] ?? 'unknown',
            ]);

            // Send payment failed notification to user
        }
    }

    /**
     * Handle successful transfer
     */
    private function handleTransferSuccess(array $data): void
    {
        $reference = $data['reference'];
        $payout = Payout::where('reference', $reference)->first();

        if (!$payout) {
            Log::warning('Payout not found for transfer success', ['reference' => $reference]);
            return;
        }

        // Only mark as completed if it's currently processing
        if ($payout->status === 'PROCESSING') {
            $payout->markAsCompleted([
                'provider_reference' => $data['reference'] ?? null,
                'provider_transfer_code' => $data['transfer_code'] ?? null,
                'provider_response' => $data,
            ]);

            Log::info('Transfer completed successfully', [
                'payout_id' => $payout->id,
                'reference' => $reference,
                'amount' => $data['amount'] / 100,
            ]);
        } else {
            Log::warning('Transfer success webhook for non-processing payout', [
                'payout_id' => $payout->id,
                'current_status' => $payout->status,
                'reference' => $reference,
            ]);
        }
    }

    /**
     * Handle failed transfer
     */
    private function handleTransferFailed(array $data): void
    {
        $reference = $data['reference'];
        $payout = Payout::where('reference', $reference)->first();

        if (!$payout) {
            Log::warning('Payout not found for transfer failed', ['reference' => $reference]);
            return;
        }

        $failureReason = $data['reason'] ?? 'Transfer failed';

        // Mark as failed
        $payout->markAsFailed($failureReason);

        Log::error('Transfer failed', [
            'payout_id' => $payout->id,
            'reference' => $reference,
            'reason' => $failureReason,
        ]);
    }

    /**
     * Handle reversed transfer
     */
    private function handleTransferReversed(array $data): void
    {
        $reference = $data['reference'];
        $payout = Payout::where('reference', $reference)->first();

        if (!$payout) {
            Log::warning('Payout not found for transfer reversed', ['reference' => $reference]);
            return;
        }

        // Mark as failed with reversal reason
        $payout->markAsFailed('Transfer was reversed: ' . ($data['reason'] ?? 'Unknown reason'));

        Log::warning('Transfer reversed', [
            'payout_id' => $payout->id,
            'reference' => $reference,
            'reason' => $data['reason'] ?? 'Unknown',
        ]);
    }
}
