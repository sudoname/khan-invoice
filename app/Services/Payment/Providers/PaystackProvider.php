<?php

namespace App\Services\Payment\Providers;

use App\Services\PaystackService;
use App\Services\Payment\DTO\PaymentInitializationResult;
use App\Services\Payment\DTO\PaymentVerificationResult;
use Illuminate\Support\Facades\Log;

class PaystackProvider implements PaymentProviderInterface
{
    public function __construct(
        protected PaystackService $paystackService
    ) {}

    /**
     * Initialize a payment
     */
    public function initializePayment(array $data): PaymentInitializationResult
    {
        try {
            $result = $this->paystackService->initializeTransaction([
                'email' => $data['email'],
                'amount' => $data['amount'],
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'],
                'metadata' => $data['metadata'] ?? [],
                'subaccount' => $data['subaccount'] ?? null,
            ]);

            if ($result['status']) {
                return PaymentInitializationResult::successful(
                    reference: $result['data']['reference'],
                    authorizationUrl: $result['data']['authorization_url'],
                    accessCode: $result['data']['access_code'],
                    metadata: $result['data']
                );
            }

            return PaymentInitializationResult::failed($result['message']);

        } catch (\Exception $e) {
            Log::error('PaystackProvider::initializePayment failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return PaymentInitializationResult::failed('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $reference): PaymentVerificationResult
    {
        try {
            $result = $this->paystackService->verifyTransaction($reference);

            if ($result['status'] && $result['data']['status'] === 'success') {
                $data = $result['data'];

                return PaymentVerificationResult::successful(
                    reference: $data['reference'],
                    amount: PaystackService::toNaira($data['amount']),
                    fees: PaystackService::toNaira($data['fees'] ?? 0),
                    currency: $data['currency'] ?? 'NGN',
                    channel: $data['channel'] ?? 'unknown',
                    paidAt: $data['paid_at'] ?? now()->toIso8601String(),
                    metadata: $data
                );
            }

            return PaymentVerificationResult::failed(
                $result['message'] ?? 'Payment verification failed',
                $reference
            );

        } catch (\Exception $e) {
            Log::error('PaystackProvider::verifyPayment failed', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);

            return PaymentVerificationResult::failed(
                'Payment verification failed: ' . $e->getMessage(),
                $reference
            );
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return $this->paystackService->verifyWebhookSignature($payload, $signature);
    }

    /**
     * Parse webhook payload
     */
    public function parseWebhookPayload(array $payload): array
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];

        return [
            'event_type' => $event,
            'event_id' => $data['id'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => $data['status'] ?? null,
            'amount' => isset($data['amount']) ? PaystackService::toNaira($data['amount']) : null,
            'fees' => isset($data['fees']) ? PaystackService::toNaira($data['fees']) : null,
            'currency' => $data['currency'] ?? 'NGN',
            'channel' => $data['channel'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'customer' => [
                'email' => $data['customer']['email'] ?? null,
                'phone' => $data['customer']['phone'] ?? null,
            ],
            'metadata' => $data['metadata'] ?? [],
            'full_data' => $data,
        ];
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'paystack';
    }

    /**
     * Calculate transaction fees (Paystack Nigeria)
     */
    public function calculateFees(float $amount, string $currency = 'NGN'): float
    {
        if ($currency !== 'NGN') {
            return 0.0;
        }

        $fee = $amount * 0.015; // 1.5%

        if ($fee > 2000) {
            $fee = 2000; // Cap at ₦2,000
        }

        $feeWithVAT = $fee * 1.0;

        return round($feeWithVAT, 2);
    }

    /**
     * Check if provider supports currency
     */
    public function supportsCurrency(string $currency): bool
    {
        return in_array($currency, ['NGN', 'USD', 'GHS', 'ZAR', 'KES']);
    }
}
