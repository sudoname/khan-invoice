<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\WhatsApp\ProcessInboundWhatsAppMessageJob;
use App\Models\WhatsApp\WaAccount;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Verify webhook endpoint (GET request from Meta).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $verifyToken = config('whatsapp.meta.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Verification failed', 403);
    }

    /**
     * Receive webhook events (POST request from Meta).
     */
    public function receive(Request $request)
    {
        // Verify signature
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return response('Signature verification failed', 403);
        }

        $payload = $request->all();

        Log::info('WhatsApp webhook received', [
            'payload' => $payload,
        ]);

        // Parse webhook payload
        $parsed = $this->whatsAppService->parseWebhookPayload($payload);

        // Process messages
        foreach ($parsed['messages'] as $messageData) {
            $this->processIncomingMessage($messageData);
        }

        // Process statuses (delivered, read, etc.)
        foreach ($parsed['statuses'] as $statusData) {
            $this->processStatusUpdate($statusData);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Process incoming message.
     */
    protected function processIncomingMessage(array $messageData): void
    {
        $phoneNumberId = $messageData['phone_number_id'];
        $from = $messageData['from'];
        $providerMessageId = $messageData['id'];
        $text = $messageData['text'];
        $type = $messageData['type'];

        // Find account by phone_number_id
        $account = WaAccount::where('phone_number_id', $phoneNumberId)
            ->where('status', 'connected')
            ->first();

        if (!$account) {
            Log::warning('WhatsApp account not found for phone_number_id', [
                'phone_number_id' => $phoneNumberId,
            ]);
            return;
        }

        // Check for duplicate message (idempotency)
        $existingMessage = \App\Models\WhatsApp\WaMessage::where('provider_message_id', $providerMessageId)
            ->first();

        if ($existingMessage) {
            Log::info('Duplicate message received, skipping', [
                'provider_message_id' => $providerMessageId,
            ]);
            return;
        }

        // Normalize phone to E.164
        $phoneE164 = WhatsAppService::normalizePhoneToE164($from);

        // Get or create contact
        $contact = \App\Models\WhatsApp\WaContact::findOrCreateByPhone(
            $account->user_id,
            $phoneE164,
            $messageData['profile']['name'] ?? null
        );

        // Get or create active conversation
        $conversation = $contact->activeConversation();
        if (!$conversation) {
            $conversation = \App\Models\WhatsApp\WaConversation::create([
                'user_id' => $account->user_id,
                'wa_contact_id' => $contact->id,
                'status' => 'open',
                'state' => 'idle',
            ]);
        }

        // Handle interactive responses (button clicks)
        if ($type === 'interactive' && isset($messageData['interactive'])) {
            $interactive = $messageData['interactive'];
            if ($interactive['type'] === 'button_reply') {
                $text = $interactive['button_reply']['title'] ?? $text;
            } elseif ($interactive['type'] === 'list_reply') {
                $text = $interactive['list_reply']['title'] ?? $text;
            }
        }

        // Create inbound message
        $message = \App\Models\WhatsApp\WaMessage::createInbound(
            $account->user_id,
            $conversation->id,
            $text,
            $type,
            $providerMessageId,
            $messageData['raw'] ?? []
        );

        // Update contact last seen
        $contact->updateLastSeen();

        // Queue processing job
        ProcessInboundWhatsAppMessageJob::dispatch(
            $conversation->id,
            $message->id
        );

        Log::info('Inbound WhatsApp message queued for processing', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);
    }

    /**
     * Process status update (delivered, read, etc.).
     */
    protected function processStatusUpdate(array $statusData): void
    {
        $providerMessageId = $statusData['id'];
        $status = $statusData['status'];

        $message = \App\Models\WhatsApp\WaMessage::where('provider_message_id', $providerMessageId)
            ->first();

        if (!$message) {
            Log::info('Message not found for status update', [
                'provider_message_id' => $providerMessageId,
                'status' => $status,
            ]);
            return;
        }

        // Update message status
        match ($status) {
            'sent' => $message->markSent($providerMessageId),
            'delivered' => $message->markDelivered(),
            'read' => $message->markRead(),
            'failed' => $message->markFailed('Message failed'),
            default => null,
        };

        Log::info('Message status updated', [
            'message_id' => $message->id,
            'status' => $status,
        ]);
    }

    /**
     * Verify webhook signature.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $appSecret = config('whatsapp.meta.app_secret');

        if (!$appSecret) {
            // If app_secret not configured, skip verification (dev mode)
            Log::warning('App secret not configured, skipping signature verification');
            return true;
        }

        return $this->whatsAppService->verifyWebhookSignature(
            $payload,
            $signature,
            $appSecret
        );
    }
}
