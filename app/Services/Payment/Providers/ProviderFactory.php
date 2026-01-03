<?php

namespace App\Services\Payment\Providers;

use App\Services\PaystackService;
use App\Services\Payment\Exceptions\UnsupportedProviderException;

class ProviderFactory
{
    /**
     * Create a payment provider instance
     */
    public static function make(string $provider): PaymentProviderInterface
    {
        return match(strtolower($provider)) {
            'paystack' => new PaystackProvider(app(PaystackService::class)),
            default => throw new UnsupportedProviderException("Payment provider '{$provider}' is not supported")
        };
    }

    /**
     * Get default provider
     */
    public static function getDefault(): PaymentProviderInterface
    {
        return self::make(config('payment.default_provider', 'paystack'));
    }

    /**
     * Get list of supported providers
     */
    public static function getSupportedProviders(): array
    {
        return ['paystack'];
    }
}
