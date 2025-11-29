<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
    }

    /**
     * Initialize a payment transaction
     *
     * @param array $data
     * @return array
     */
    public function initializeTransaction(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', [
                'email' => $data['email'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'],
                'metadata' => $data['metadata'] ?? [],
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transaction initialized successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to initialize transaction',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initialization error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while initializing payment',
                'data' => null,
            ];
        }
    }

    /**
     * Verify a payment transaction
     *
     * @param string $reference
     * @return array
     */
    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transaction/verify/' . $reference);

            if ($response->successful()) {
                $data = $response->json('data');

                return [
                    'status' => true,
                    'message' => 'Transaction verified successfully',
                    'data' => $data,
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to verify transaction',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verification error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while verifying payment',
                'data' => null,
            ];
        }
    }

    /**
     * Generate a unique payment reference
     *
     * @return string
     */
    public static function generateReference(): string
    {
        return 'KI_' . time() . '_' . uniqid();
    }

    /**
     * Convert amount to kobo (Paystack uses kobo)
     *
     * @param float $amount
     * @return int
     */
    public static function toKobo(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Convert kobo to naira
     *
     * @param int $kobo
     * @return float
     */
    public static function toNaira(int $kobo): float
    {
        return $kobo / 100;
    }
}
