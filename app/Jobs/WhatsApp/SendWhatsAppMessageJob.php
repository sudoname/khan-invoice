<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\WaAccount;
use App\Models\WhatsApp\WaMessage;
use App\Services\WhatsApp\Contracts\WhatsAppClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $messageId;

    /**
     * Number of times to retry on failure.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 60;

    /**
     * Delay between retries in seconds.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(int $messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppClientInterface $client): void
    {
        Log::info('Sending WhatsApp message', [
            'message_id' => $this->messageId,
        ]);

        // Load message
        $message = WaMessage::findOrFail($this->messageId);

        // Check if already sent
        if (in_array($message->status, ['sent', 'delivered', 'read'])) {
            Log::info('Message already sent, skipping', [
                'message_id' => $message->id,
                'status' => $message->status,
            ]);
            return;
        }

        // Get conversation and contact
        $conversation = $message->conversation;
        $contact = $conversation->contact;

        // Get user's WhatsApp account
        $account = WaAccount::where('user_id', $conversation->user_id)
            ->where('status', 'connected')
            ->first();

        if (!$account) {
            $message->markFailed('No connected WhatsApp account found');
            Log::error('No connected WhatsApp account', [
                'user_id' => $conversation->user_id,
                'message_id' => $message->id,
            ]);
            return;
        }

        try {
            // Send based on message type
            $result = match ($message->message_type) {
                'text' => $this->sendTextMessage($client, $account, $contact->phone_e164, $message),
                'interactive' => $this->sendInteractiveMessage($client, $account, $contact->phone_e164, $message),
                default => throw new \InvalidArgumentException("Unsupported message type: {$message->message_type}"),
            };

            if ($result['success']) {
                $message->markSent($result['message_id']);

                Log::info('WhatsApp message sent successfully', [
                    'message_id' => $message->id,
                    'provider_message_id' => $result['message_id'],
                ]);
            } else {
                $message->markFailed($result['error'] ?? 'Unknown error');

                Log::error('Failed to send WhatsApp message', [
                    'message_id' => $message->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            $message->markFailed($e->getMessage());

            Log::error('Exception sending WhatsApp message', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send text message.
     */
    protected function sendTextMessage(
        WhatsAppClientInterface $client,
        WaAccount $account,
        string $toE164,
        WaMessage $message
    ): array {
        return $client->sendText(
            $account,
            $toE164,
            $message->body,
            $message->meta['options'] ?? []
        );
    }

    /**
     * Send interactive message (buttons or list).
     */
    protected function sendInteractiveMessage(
        WhatsAppClientInterface $client,
        WaAccount $account,
        string $toE164,
        WaMessage $message
    ): array {
        $meta = $message->meta ?? [];
        $interactiveType = $meta['type'] ?? 'buttons';

        if ($interactiveType === 'buttons') {
            return $client->sendInteractiveButtons(
                $account,
                $toE164,
                $message->body,
                $meta['buttons'] ?? [],
                $meta['header'] ?? null,
                $meta['footer'] ?? null
            );
        }

        if ($interactiveType === 'list') {
            return $client->sendInteractiveList(
                $account,
                $toE164,
                $message->body,
                $meta['button_text'] ?? 'View Options',
                $meta['sections'] ?? [],
                $meta['header'] ?? null,
                $meta['footer'] ?? null
            );
        }

        throw new \InvalidArgumentException("Unsupported interactive type: {$interactiveType}");
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppMessageJob failed permanently', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);

        // Mark message as failed
        try {
            $message = WaMessage::find($this->messageId);
            if ($message) {
                $message->markFailed($exception->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark message as failed', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
