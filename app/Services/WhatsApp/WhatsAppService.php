<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WaAccount;
use App\Models\WhatsApp\WaContact;
use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\WaMessage;
use App\Models\WhatsApp\WaOptOut;
use App\Services\WhatsApp\Contracts\WhatsAppClientInterface;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected WhatsAppClientInterface $client;

    public function __construct(WhatsAppClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Send a text message to a contact.
     */
    public function sendText(
        int $userId,
        string $phoneE164,
        string $text,
        ?int $conversationId = null
    ): ?WaMessage {
        // Check opt-out
        if (WaOptOut::hasOptedOut($userId, $phoneE164)) {
            Log::info('Message not sent - user opted out', ['phone' => $phoneE164]);
            return null;
        }

        $account = WaAccount::where('user_id', $userId)->where('status', 'connected')->first();
        if (!$account) {
            throw new \Exception('No connected WhatsApp account found');
        }

        // Get or create contact and conversation
        $contact = WaContact::findOrCreateByPhone($userId, $phoneE164);

        if (!$conversationId) {
            $conversation = $contact->activeConversation();
            if (!$conversation) {
                $conversation = WaConversation::create([
                    'user_id' => $userId,
                    'wa_contact_id' => $contact->id,
                    'status' => 'open',
                    'state' => 'idle',
                ]);
            }
            $conversationId = $conversation->id;
        }

        // Create outbound message
        $message = WaMessage::createOutbound($userId, $conversationId, $text);

        // Queue sending
        SendWhatsAppMessageJob::dispatch($message->id);

        return $message;
    }

    /**
     * Send a message with template variables replaced.
     */
    public function sendTemplateMessage(
        int $userId,
        string $phoneE164,
        string $template,
        array $variables,
        ?int $conversationId = null
    ): ?WaMessage {
        $text = $this->replaceVariables($template, $variables);
        return $this->sendText($userId, $phoneE164, $text, $conversationId);
    }

    /**
     * Send interactive buttons.
     */
    public function sendButtons(
        int $userId,
        int $conversationId,
        string $bodyText,
        array $buttons,
        ?string $headerText = null,
        ?string $footerText = null
    ): ?WaMessage {
        $conversation = WaConversation::findOrFail($conversationId);
        $contact = $conversation->contact;

        // Check opt-out
        if (WaOptOut::hasOptedOut($userId, $contact->phone_e164)) {
            return null;
        }

        $account = WaAccount::where('user_id', $userId)->where('status', 'connected')->first();
        if (!$account) {
            throw new \Exception('No connected WhatsApp account found');
        }

        // Create outbound message with interactive type
        $message = WaMessage::createOutbound(
            $userId,
            $conversationId,
            $bodyText,
            'interactive',
            [
                'type' => 'buttons',
                'buttons' => $buttons,
                'header' => $headerText,
                'footer' => $footerText,
            ]
        );

        // Queue sending
        SendWhatsAppMessageJob::dispatch($message->id);

        return $message;
    }

    /**
     * Replace template variables.
     */
    protected function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }

    /**
     * Normalize phone number to E.164 format.
     */
    public static function normalizePhoneToE164(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with country code (Nigeria: 234)
        if (substr($phone, 0, 1) === '0') {
            $phone = '234' . substr($phone, 1);
        }

        // Add + prefix
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Parse incoming webhook payload.
     */
    public function parseWebhookPayload(array $payload): array
    {
        $result = [
            'messages' => [],
            'statuses' => [],
        ];

        if (!isset($payload['entry'])) {
            return $result;
        }

        foreach ($payload['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];

                // Extract phone_number_id to identify account
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                // Parse messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $result['messages'][] = [
                            'phone_number_id' => $phoneNumberId,
                            'from' => $message['from'] ?? null,
                            'id' => $message['id'] ?? null,
                            'timestamp' => $message['timestamp'] ?? null,
                            'type' => $message['type'] ?? 'text',
                            'text' => $message['text']['body'] ?? null,
                            'image' => $message['image'] ?? null,
                            'interactive' => $message['interactive'] ?? null,
                            'profile' => $value['contacts'][0]['profile'] ?? null,
                            'raw' => $message,
                        ];
                    }
                }

                // Parse statuses (delivered, read)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $result['statuses'][] = [
                            'phone_number_id' => $phoneNumberId,
                            'id' => $status['id'] ?? null,
                            'status' => $status['status'] ?? null,
                            'timestamp' => $status['timestamp'] ?? null,
                            'recipient_id' => $status['recipient_id'] ?? null,
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $appSecret): bool
    {
        // Meta uses X-Hub-Signature-256: sha256=<hash>
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expectedSignature, $signature);
    }
}
