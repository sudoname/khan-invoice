<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\DTO\PaymentInitializationResult;
use App\Services\Payment\DTO\PaymentVerificationResult;

interface PaymentProviderInterface
{
    /**
     * Initialize a payment
     */
    public function initializePayment(array $data): PaymentInitializationResult;

    /**
     * Verify a payment
     */
    public function verifyPayment(string $reference): PaymentVerificationResult;

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse webhook payload
     */
    public function parseWebhookPayload(array $payload): array;

    /**
     * Get provider name
     */
    public function getProviderName(): string;

    /**
     * Calculate transaction fees
     */
    public function calculateFees(float $amount, string $currency = 'NGN'): float;

    /**
     * Check if provider supports currency
     */
    public function supportsCurrency(string $currency): bool;
}
