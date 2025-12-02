<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackSubaccountService
{
    protected $secretKey;
    protected $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Create a Paystack subaccount for split payment
     */
    public function createSubaccount(array $data): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subaccount', [
                'business_name' => $data['business_name'],
                'settlement_bank' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'percentage_charge' => 0, // Platform takes the profit, not a percentage
                'description' => $data['description'] ?? 'Invoice merchant',
            ]);

            if ($response->successful() && $response->json('status')) {
                $subaccountCode = $response->json('data.subaccount_code');

                Log::info('Paystack subaccount created', [
                    'subaccount_code' => $subaccountCode,
                    'business_name' => $data['business_name'],
                ]);

                return $subaccountCode;
            }

            Log::error('Failed to create Paystack subaccount', [
                'response' => $response->json(),
                'data' => $data,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating Paystack subaccount: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Get bank code from bank name
     */
    public function getBankCode(string $bankName): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank');

            if ($response->successful() && $response->json('status')) {
                $banks = $response->json('data');

                // Search for bank by name (case-insensitive, partial match)
                foreach ($banks as $bank) {
                    if (stripos($bank['name'], $bankName) !== false || stripos($bankName, $bank['name']) !== false) {
                        return $bank['code'];
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting bank code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate account number
     */
    public function validateAccount(string $accountNumber, string $bankCode): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            if ($response->successful() && $response->json('status')) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception validating account: ' . $e->getMessage());
            return null;
        }
    }
}
