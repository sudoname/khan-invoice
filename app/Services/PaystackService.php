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
            $payload = [
                'email' => $data['email'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'reference' => $data['reference'],
                'callback_url' => $data['callback_url'],
                'metadata' => $data['metadata'] ?? [],
            ];

            // Add subaccount if provided
            if (isset($data['subaccount'])) {
                $payload['subaccount'] = $data['subaccount'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transaction/initialize', $payload);

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

    /**
     * Create a subaccount for a business
     *
     * @param array $data
     * @return array
     */
    public function createSubaccount(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subaccount', [
                'business_name' => $data['business_name'],
                'settlement_bank' => $data['settlement_bank'], // Bank code
                'account_number' => $data['account_number'],
                'percentage_charge' => $data['percentage_charge'] ?? 0, // Platform fee percentage
                'description' => $data['description'] ?? '',
                'primary_contact_email' => $data['primary_contact_email'] ?? null,
                'primary_contact_name' => $data['primary_contact_name'] ?? null,
                'primary_contact_phone' => $data['primary_contact_phone'] ?? null,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Subaccount created successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to create subaccount',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack subaccount creation error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while creating subaccount',
                'data' => null,
            ];
        }
    }

    /**
     * List available Nigerian banks
     *
     * @return array
     */
    public function listBanks(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank', [
                'country' => 'nigeria',
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Banks retrieved successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to retrieve banks',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list banks error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving banks',
                'data' => null,
            ];
        }
    }

    /**
     * Resolve account number to get account name
     *
     * @param string $accountNumber
     * @param string $bankCode
     * @return array
     */
    public function resolveAccountNumber(string $accountNumber, string $bankCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Account resolved successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to resolve account',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack account resolution error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while resolving account',
                'data' => null,
            ];
        }
    }

    /**
     * Get bank code by bank name
     *
     * @param string $bankName
     * @return string|null
     */
    public function getBankCode(string $bankName): ?string
    {
        $banks = $this->listBanks();

        if (!$banks['status']) {
            return null;
        }

        foreach ($banks['data'] as $bank) {
            if (stripos($bank['name'], $bankName) !== false) {
                return $bank['code'];
            }
        }

        return null;
    }
}
