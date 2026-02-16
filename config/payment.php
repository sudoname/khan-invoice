<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    |
    | This defines the default payment provider to use for processing
    | payments. Currently supported: paystack
    |
    */

    'default_provider' => env('PAYMENT_PROVIDER', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Payment Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each payment provider
    |
    */

    'providers' => [
        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
        ],

        'flutterwave' => [
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'payment_link_expiry_hours' => env('PAYMENT_LINK_EXPIRY_HOURS', 168), // 7 days default

    'payment_attempt_expiry_hours' => env('PAYMENT_ATTEMPT_EXPIRY_HOURS', 24), // 24 hours default

    /*
    |--------------------------------------------------------------------------
    | Service Charges
    |--------------------------------------------------------------------------
    |
    | Platform service charge configuration
    |
    */

    'service_charge' => [
        'enabled' => env('SERVICE_CHARGE_ENABLED', false),
        'percentage' => env('SERVICE_CHARGE_PERCENTAGE', 0),
        'minimum' => env('SERVICE_CHARGE_MINIMUM', 0),
        'maximum' => env('SERVICE_CHARGE_MAXIMUM', null),
    ],

];
