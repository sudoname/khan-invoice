<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
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
     * Send WhatsApp message via Termii API.
     *
     * @param string $phoneNumber Recipient phone number (E.164 format: +234...)
     * @param string $message Message content
     * @return array [status => bool, message => string, data => array]
     */
    public function sendWhatsApp(string $phoneNumber, string $message): array
    {
        try {
            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            $payload = [
                'to' => $phoneNumber,
                'from' => $this->senderId,
                'type' => 'plain',
                'channel' => 'whatsapp',
                'api_key' => $this->apiKey,
                'data' => [
                    'message' => $message,
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send/message', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('WhatsApp message sent successfully', [
                    'message_id' => $data['message_id'] ?? null,
                    'to' => $phoneNumber,
                    'balance' => $data['balance'] ?? null,
                ]);

                return [
                    'status' => true,
                    'message' => 'WhatsApp message sent successfully',
                    'data' => [
                        'message_id' => $data['message_id'] ?? null,
                        'balance' => $data['balance'] ?? null,
                        'to' => $phoneNumber,
                        'from' => $this->senderId,
                    ],
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Failed to send WhatsApp message';

            Log::error('Termii WhatsApp API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            return [
                'status' => false,
                'message' => $errorMessage,
                'data' => [],
            ];

        } catch (\Exception $e) {
            Log::error('Termii WhatsApp service exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while sending WhatsApp message: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get Termii account balance.
     *
     * @return array [status => bool, message => string, data => array]
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
                'data' => [],
            ];

        } catch (\Exception $e) {
            Log::error('Termii balance check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Failed to check balance: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Normalize phone number to E.164 format for Nigerian numbers.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If starts with 0, replace with +234
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '+234' . substr($phoneNumber, 1);
        }

        // If starts with 234, add +
        if (substr($phoneNumber, 0, 3) === '234') {
            $phoneNumber = '+' . $phoneNumber;
        }

        // If doesn't start with +, assume it needs +234
        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+234' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
