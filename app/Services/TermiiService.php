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

            // Use DND channel if no sender ID configured or sender ID not approved
            // DND channel doesn't require registered sender ID (good for testing)
            $channel = config('services.termii.channel', 'dnd');

            $payload = [
                'to' => $phoneNumber,
                'sms' => $message,
                'type' => 'plain',
                'channel' => $channel,
                'api_key' => $this->apiKey,
            ];

            // Only include sender ID for generic/whatsapp channels (requires registration)
            if (in_array($channel, ['generic', 'whatsapp'])) {
                $payload['from'] = $this->senderId;
            }

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

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Failed to send SMS',
                'data' => null,
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
