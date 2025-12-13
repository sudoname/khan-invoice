<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $fromNumber;
    protected string $baseUrl;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->fromNumber = config('services.twilio.whatsapp_from');
        $this->baseUrl = 'https://api.twilio.com/2010-04-01';
    }

    /**
     * Send WhatsApp message via Twilio API.
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

            // Prepare request
            $url = "{$this->baseUrl}/Accounts/{$this->accountSid}/Messages.json";

            $response = Http::asForm()
                ->withBasicAuth($this->accountSid, $this->authToken)
                ->post($url, [
                    'From' => "whatsapp:{$this->fromNumber}",
                    'To' => "whatsapp:{$phoneNumber}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('WhatsApp message sent successfully', [
                    'sid' => $data['sid'] ?? null,
                    'to' => $phoneNumber,
                    'status' => $data['status'] ?? null,
                ]);

                return [
                    'status' => true,
                    'message' => 'WhatsApp message sent successfully',
                    'data' => [
                        'message_id' => $data['sid'] ?? null,
                        'status' => $data['status'] ?? 'queued',
                        'to' => $phoneNumber,
                        'from' => $this->fromNumber,
                    ],
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Failed to send WhatsApp message';

            Log::error('WhatsApp API error', [
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
            Log::error('WhatsApp service exception', [
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
     * Get Twilio account balance and message count.
     *
     * @return array [status => bool, message => string, data => array]
     */
    public function getBalance(): array
    {
        try {
            $url = "{$this->baseUrl}/Accounts/{$this->accountSid}/Balance.json";

            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'status' => true,
                    'message' => 'Balance retrieved successfully',
                    'data' => [
                        'balance' => $data['balance'] ?? '0',
                        'currency' => $data['currency'] ?? 'USD',
                    ],
                ];
            }

            return [
                'status' => false,
                'message' => 'Failed to retrieve balance',
                'data' => [],
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp balance check failed', [
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
