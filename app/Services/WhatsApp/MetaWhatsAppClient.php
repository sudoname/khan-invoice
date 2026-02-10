<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WaAccount;
use App\Services\WhatsApp\Contracts\WhatsAppClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppClient implements WhatsAppClientInterface
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.meta.base_url');
    }

    /**
     * Send a text message.
     */
    public function sendText(WaAccount $account, string $toE164, string $text, array $options = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($toE164),
            'type' => 'text',
            'text' => [
                'body' => $text,
            ],
        ];

        if (isset($options['preview_url'])) {
            $payload['text']['preview_url'] = $options['preview_url'];
        }

        return $this->sendRequest($account, '/messages', $payload);
    }

    /**
     * Send a template message.
     */
    public function sendTemplate(
        WaAccount $account,
        string $toE164,
        string $templateName,
        string $languageCode,
        array $components = []
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($toE164),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendRequest($account, '/messages', $payload);
    }

    /**
     * Send interactive buttons.
     */
    public function sendInteractiveButtons(
        WaAccount $account,
        string $toE164,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): array {
        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => [
                'buttons' => array_map(function ($button, $index) {
                    return [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $button['id'] ?? "button_{$index}",
                            'title' => substr($button['title'], 0, 20), // Max 20 chars
                        ],
                    ];
                }, $buttons, array_keys($buttons)),
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($toE164),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendRequest($account, '/messages', $payload);
    }

    /**
     * Send interactive list.
     */
    public function sendInteractiveList(
        WaAccount $account,
        string $toE164,
        string $bodyText,
        string $buttonText,
        array $sections,
        ?string $headerText = null,
        ?string $footerText = null
    ): array {
        $interactive = [
            'type' => 'list',
            'body' => ['text' => $bodyText],
            'action' => [
                'button' => $buttonText,
                'sections' => $sections,
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($toE164),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->sendRequest($account, '/messages', $payload);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(WaAccount $account, string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendRequest($account, '/messages', $payload);
    }

    /**
     * Send HTTP request to Meta WhatsApp API.
     */
    protected function sendRequest(WaAccount $account, string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . '/' . $account->phone_number_id . $endpoint;

        try {
            $response = Http::withToken($account->access_token)
                ->timeout(30)
                ->retry(3, 1000, function ($exception, $request) {
                    // Retry on 429 (rate limit) or 5xx errors
                    return $exception instanceof \Illuminate\Http\Client\RequestException
                        && in_array($exception->response->status(), [429, 500, 502, 503, 504]);
                })
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp message sent', [
                    'account_id' => $account->id,
                    'to' => $payload['to'] ?? null,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                    'response' => $data,
                ];
            }

            // Handle API error
            $error = $response->json('error.message', 'Unknown error');
            Log::error('WhatsApp API error', [
                'account_id' => $account->id,
                'status' => $response->status(),
                'error' => $error,
                'payload' => $payload,
            ]);

            $account->markError($error);

            return [
                'success' => false,
                'error' => $error,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp request exception', [
                'account_id' => $account->id,
                'message' => $e->getMessage(),
            ]);

            $account->markError($e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize phone number to E.164 without +.
     */
    protected function normalizePhone(string $phone): string
    {
        // Meta API expects E.164 without +
        return ltrim($phone, '+');
    }
}
