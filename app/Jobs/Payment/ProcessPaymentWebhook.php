<?php

namespace App\Jobs\Payment;

use App\Models\Payment\PaymentEvent;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\Payout;
use App\Services\Payment\PaymentService;
use App\Services\Payment\Providers\ProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        protected string $provider,
        protected array $payload
    ) {}

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            $payloadHash = hash('sha256', json_encode($this->payload));

            if (PaymentEvent::isDuplicate($payloadHash)) {
                Log::info('Duplicate webhook ignored', [
                    'provider' => $this->provider,
                    'payload_hash' => $payloadHash,
                ]);
                return;
            }

            $eventData = $this->parseWebhookData();

            $paymentEvent = PaymentEvent::create([
                'provider' => $this->provider,
                'event_type' => $eventData['event_type'],
                'reference' => $eventData['reference'],
                'event_id' => $eventData['event_id'],
                'payload_hash' => $payloadHash,
                'payload_json' => $this->payload,
                'status' => 'RECEIVED',
                'received_at' => now(),
            ]);

            $paymentEvent->markAsProcessing();

            $this->processEvent($eventData, $paymentEvent);

            $paymentEvent->markAsProcessed();

            Log::info('Webhook processed successfully', [
                'provider' => $this->provider,
                'event_type' => $eventData['event_type'],
                'reference' => $eventData['reference'],
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($paymentEvent)) {
                $paymentEvent->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Parse webhook data
     */
    protected function parseWebhookData(): array
    {
        $provider = ProviderFactory::make($this->provider);
        return $provider->parseWebhookPayload($this->payload);
    }

    /**
     * Process the webhook event
     */
    protected function processEvent(array $eventData, PaymentEvent $paymentEvent): void
    {
        $eventType = $eventData['event_type'];

        match($eventType) {
            'charge.success' => $this->handleChargeSuccess($eventData),
            'transfer.success' => $this->handleTransferSuccess($eventData),
            'transfer.failed' => $this->handleTransferFailed($eventData),
            default => Log::info('Unhandled webhook event', ['event_type' => $eventType]),
        };
    }

    /**
     * Handle successful charge event
     */
    protected function handleChargeSuccess(array $eventData): void
    {
        $reference = $eventData['reference'];

        if (!$reference) {
            Log::warning('Charge success event missing reference', ['data' => $eventData]);
            return;
        }

        $paymentAttempt = PaymentAttempt::where('reference', $reference)->first();

        if (!$paymentAttempt) {
            Log::warning('Payment attempt not found for reference', ['reference' => $reference]);
            return;
        }

        if ($paymentAttempt->isSuccessful()) {
            Log::info('Payment already marked as successful', ['reference' => $reference]);
            return;
        }

        $paymentService = app(PaymentService::class);
        $paymentService->setProvider($this->provider);
        $paymentService->verifyPayment($reference);
    }

    /**
     * Handle successful transfer event (for payouts)
     */
    protected function handleTransferSuccess(array $eventData): void
    {
        try {
            $reference = $eventData['reference'] ?? null;

            if (!$reference) {
                Log::warning('Transfer success event missing reference', ['data' => $eventData]);
                return;
            }

            // Find payout by reference
            $payout = Payout::where('reference', $reference)->first();

            if (!$payout) {
                // Try finding by provider reference
                $payout = Payout::where('provider_reference', $reference)->first();
            }

            if (!$payout) {
                Log::warning('Payout not found for transfer reference', [
                    'reference' => $reference,
                    'event_data' => $eventData,
                ]);
                return;
            }

            // If already completed, skip
            if ($payout->status === 'COMPLETED') {
                Log::info('Payout already marked as completed', [
                    'payout_id' => $payout->id,
                    'reference' => $reference,
                ]);
                return;
            }

            // Mark payout as completed
            $payout->markAsCompleted([
                'provider_reference' => $eventData['reference'] ?? $payout->provider_reference,
                'provider_transfer_code' => $eventData['full_data']['transfer_code'] ?? $payout->provider_transfer_code,
                'provider_response' => $eventData['full_data'] ?? $payout->provider_response,
            ]);

            Log::info('Payout marked as completed via webhook', [
                'payout_id' => $payout->id,
                'reference' => $reference,
                'amount' => $payout->net_amount,
                'merchant_account' => $payout->merchantAccount->bank_name . ' - ' . $payout->merchantAccount->account_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling transfer success webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_data' => $eventData,
            ]);
            throw $e;
        }
    }

    /**
     * Handle failed transfer event (for payouts)
     */
    protected function handleTransferFailed(array $eventData): void
    {
        try {
            $reference = $eventData['reference'] ?? null;

            if (!$reference) {
                Log::warning('Transfer failed event missing reference', ['data' => $eventData]);
                return;
            }

            // Find payout by reference
            $payout = Payout::where('reference', $reference)->first();

            if (!$payout) {
                // Try finding by provider reference
                $payout = Payout::where('provider_reference', $reference)->first();
            }

            if (!$payout) {
                Log::warning('Payout not found for failed transfer reference', [
                    'reference' => $reference,
                    'event_data' => $eventData,
                ]);
                return;
            }

            // If already failed, skip
            if ($payout->status === 'FAILED') {
                Log::info('Payout already marked as failed', [
                    'payout_id' => $payout->id,
                    'reference' => $reference,
                ]);
                return;
            }

            // Mark payout as failed
            $failureReason = $eventData['full_data']['message'] ?? 'Transfer failed (reason unknown)';
            $payout->markAsFailed($failureReason);

            Log::error('Payout marked as failed via webhook', [
                'payout_id' => $payout->id,
                'reference' => $reference,
                'reason' => $failureReason,
                'merchant_account' => $payout->merchantAccount->bank_name . ' - ' . $payout->merchantAccount->account_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling transfer failed webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_data' => $eventData,
            ]);
            throw $e;
        }
    }
}
