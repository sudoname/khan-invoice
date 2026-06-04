<?php

namespace App\Services;

use App\Models\PaymentSetting;
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
            // Get platform commission percentage from settings (default 2%)
            $platformCommissionPercentage = (float) PaymentSetting::get('service_charge_percentage', 2);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subaccount', [
                'business_name' => $data['business_name'],
                'settlement_bank' => $data['bank_code'],
                'account_number' => $data['account_number'],
                'percentage_charge' => $platformCommissionPercentage, // Platform commission (e.g., 2% = platform keeps 2%, merchant gets 98% auto-settled)
                'description' => $data['description'] ?? 'Invoice merchant',
            ]);

            if ($response->successful() && $response->json('status')) {
                $subaccountCode = $response->json('data.subaccount_code');
                $merchantPercentage = 100 - $platformCommissionPercentage;

                Log::info('Paystack subaccount created with auto-settlement', [
                    'subaccount_code' => $subaccountCode,
                    'business_name' => $data['business_name'],
                    'platform_commission' => $platformCommissionPercentage . '%',
                    'merchant_receives' => $merchantPercentage . '%',
                    'settlement_bank' => $data['bank_code'],
                    'account_number' => $data['account_number'],
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
