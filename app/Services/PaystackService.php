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

    /**
     * Create a subscription plan on Paystack
     */
    public function createSubscriptionPlan(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/plan', [
                'name' => $data['name'],
                'amount' => $data['amount'] * 100, // Convert to kobo
                'interval' => $data['interval'], // daily, weekly, monthly, annually
                'description' => $data['description'] ?? '',
                'currency' => $data['currency'] ?? 'NGN',
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Subscription plan created successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to create subscription plan',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack create subscription plan error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while creating subscription plan',
                'data' => null,
            ];
        }
    }

    /**
     * Subscribe a customer to a plan
     */
    public function subscribeCustomer(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subscription', [
                'customer' => $data['customer'], // Customer email or code
                'plan' => $data['plan'], // Plan code
                'authorization' => $data['authorization'] ?? null, // Authorization code from previous transaction
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Customer subscribed successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to subscribe customer',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack subscribe customer error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while subscribing customer',
                'data' => null,
            ];
        }
    }

    /**
     * Disable/Cancel a subscription
     */
    public function cancelSubscription(string $code, string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subscription/disable', [
                'code' => $code,
                'token' => $token,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Subscription cancelled successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to cancel subscription',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack cancel subscription error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while cancelling subscription',
                'data' => null,
            ];
        }
    }

    /**
     * Enable a subscription
     */
    public function enableSubscription(string $code, string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/subscription/enable', [
                'code' => $code,
                'token' => $token,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Subscription enabled successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to enable subscription',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack enable subscription error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while enabling subscription',
                'data' => null,
            ];
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/subscription/' . $subscriptionCode);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Subscription retrieved successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to retrieve subscription',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack get subscription error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving subscription',
                'data' => null,
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $input, string $signature): bool
    {
        return hash_hmac('sha512', $input, $this->secretKey) === $signature;
    }

    /**
     * Get public key for frontend integration
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Create a transfer recipient for payouts
     *
     * @param array $data
     * @return array
     */
    public function createTransferRecipient(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transferrecipient', [
                'type' => $data['type'] ?? 'nuban', // Nigerian Uniform Bank Account Number
                'name' => $data['name'], // Account name
                'account_number' => $data['account_number'],
                'bank_code' => $data['bank_code'],
                'currency' => $data['currency'] ?? 'NGN',
                'description' => $data['description'] ?? '',
                'metadata' => $data['metadata'] ?? [],
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfer recipient created successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to create transfer recipient',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack create transfer recipient error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while creating transfer recipient',
                'data' => null,
            ];
        }
    }

    /**
     * Initiate a transfer (payout)
     *
     * @param array $data
     * @return array
     */
    public function initiateTransfer(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer', [
                'source' => $data['source'] ?? 'balance', // Source of funds
                'amount' => $data['amount'] * 100, // Convert to kobo
                'recipient' => $data['recipient'], // Recipient code (RCP_xxx)
                'reason' => $data['reason'] ?? 'Payout',
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfer initiated successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to initiate transfer',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack initiate transfer error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while initiating transfer',
                'data' => null,
            ];
        }
    }

    /**
     * Verify a transfer
     *
     * @param string $reference
     * @return array
     */
    public function verifyTransfer(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transfer/verify/' . $reference);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfer verified successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to verify transfer',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack verify transfer error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while verifying transfer',
                'data' => null,
            ];
        }
    }

    /**
     * List transfers
     *
     * @param array $params
     * @return array
     */
    public function listTransfers(array $params = []): array
    {
        try {
            $queryParams = [
                'perPage' => $params['perPage'] ?? 50,
                'page' => $params['page'] ?? 1,
            ];

            if (isset($params['status'])) {
                $queryParams['status'] = $params['status']; // success, failed, pending, etc.
            }

            if (isset($params['from'])) {
                $queryParams['from'] = $params['from'];
            }

            if (isset($params['to'])) {
                $queryParams['to'] = $params['to'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transfer', $queryParams);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfers retrieved successfully',
                    'data' => $response->json('data'),
                    'meta' => $response->json('meta'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to retrieve transfers',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack list transfers error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving transfers',
                'data' => null,
            ];
        }
    }

    /**
     * Get transfer recipient details
     *
     * @param string $recipientCode
     * @return array
     */
    public function getTransferRecipient(string $recipientCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/transferrecipient/' . $recipientCode);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfer recipient retrieved successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to retrieve transfer recipient',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack get transfer recipient error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving transfer recipient',
                'data' => null,
            ];
        }
    }

    /**
     * Finalize a transfer (for OTP-enabled transfers)
     *
     * @param string $transferCode
     * @param string $otp
     * @return array
     */
    public function finalizeTransfer(string $transferCode, string $otp): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer/finalize_transfer', [
                'transfer_code' => $transferCode,
                'otp' => $otp,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Transfer finalized successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to finalize transfer',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack finalize transfer error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while finalizing transfer',
                'data' => null,
            ];
        }
    }

    /**
     * Bulk transfer initiation
     *
     * @param array $transfers
     * @return array
     */
    public function initiateBulkTransfer(array $transfers): array
    {
        try {
            // Format transfers array
            $formattedTransfers = array_map(function($transfer) {
                return [
                    'amount' => $transfer['amount'] * 100, // Convert to kobo
                    'recipient' => $transfer['recipient'],
                    'reference' => $transfer['reference'] ?? null,
                    'reason' => $transfer['reason'] ?? 'Bulk payout',
                ];
            }, $transfers);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer/bulk', [
                'currency' => 'NGN',
                'source' => 'balance',
                'transfers' => $formattedTransfers,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'message' => 'Bulk transfer initiated successfully',
                    'data' => $response->json('data'),
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to initiate bulk transfer',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack bulk transfer error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'An error occurred while initiating bulk transfer',
                'data' => null,
            ];
        }
    }
}
