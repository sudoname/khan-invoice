<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiService
{
    protected string $apiKey;
    protected string $senderId;
    protected string $baseUrl = 'https://api.ng.termii.com/api';

    public function __construct()
    {
        $this->apiKey = config('services.termii.api_key');
        $this->senderId = config('services.termii.sender_id', 'KhanInvoice');
    }

    /**
     * Send SMS via Termii API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Normalize phone number to international format
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            // Channel: 'dnd' = can reach DND numbers, 'generic' = standard delivery
            // Both require registered sender ID - no way around it
            $channel = config('services.termii.channel', 'dnd');

            $payload = [
                'to' => $phoneNumber,
                'from' => $this->senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => $channel,
                'api_key' => $this->apiKey,
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/sms/send', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => true,
                    'message' => 'SMS sent successfully',
                    'data' => [
                        'message_id' => $data['message_id'] ?? null,
                        'balance' => $data['balance'] ?? null,
                    ],
                ];
            }

            $errorData = $response->json();

            // Extract error message
            $errorMessage = 'Failed to send SMS';
            if (is_array($errorData)) {
                if (isset($errorData['message'])) {
                    // Handle array of error objects
                    if (is_array($errorData['message']) && isset($errorData['message'][0]['issue'])) {
                        $errorMessage = $errorData['message'][0]['issue'];

                        // Provide helpful context for common errors
                        if (str_contains($errorMessage, 'ApplicationSenderId not found')) {
                            $errorMessage .= ' - You need to register this Sender ID in Termii dashboard first. See TERMII_QUICK_START.txt';
                        }
                    } else {
                        $errorMessage = is_string($errorData['message']) ? $errorData['message'] : json_encode($errorData['message']);
                    }
                }
            }

            Log::error('Termii SMS API error', [
                'status' => $response->status(),
                'response' => $errorData,
            ]);

            return [
                'status' => false,
                'message' => $errorMessage,
                'data' => $errorData,
            ];
        } catch (\Exception $e) {
            Log::error('Termii SMS error: ' . $e->getMessage(), [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while sending SMS',
                'data' => null,
            ];
        }
    }

    /**
     * Get account balance
     *
     * @return array
     */
    public function getBalance(): array
    {
        try {
            $response = Http::get($this->baseUrl . '/get-balance', [
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => true,
                    'message' => 'Balance retrieved successfully',
                    'data' => [
                        'balance' => $data['balance'] ?? 0,
                        'currency' => $data['currency'] ?? 'NGN',
                    ],
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to retrieve balance',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Termii balance check error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'An error occurred while checking balance',
                'data' => null,
            ];
        }
    }

    /**
     * Normalize phone number to international format
     * Handles Nigerian numbers: converts 0803... to 234803...
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove spaces, hyphens, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);

        // If starts with 0, replace with 234 (Nigeria)
        if (substr($phone, 0, 1) === '0') {
            $phone = '234' . substr($phone, 1);
        }

        // If doesn't start with +, add it
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
