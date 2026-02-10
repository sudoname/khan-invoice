<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\WaMessage;
use App\Services\WhatsApp\WhatsAppAiOrchestrator;
use App\Services\WhatsApp\ActionExecutor;
use App\Services\WhatsApp\ConversationStateManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $conversationId;
    public int $messageId;

    /**
     * Number of times to retry on failure.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(int $conversationId, int $messageId)
    {
        $this->conversationId = $conversationId;
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        WhatsAppAiOrchestrator $aiOrchestrator,
        ActionExecutor $actionExecutor,
        ConversationStateManager $stateManager
    ): void {
        Log::info('Processing inbound WhatsApp message', [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
        ]);

        // Load conversation and message
        $conversation = WaConversation::findOrFail($this->conversationId);
        $message = WaMessage::findOrFail($this->messageId);

        // Check if conversation needs human handoff
        if ($stateManager->needsHumanHandoff($conversation)) {
            Log::info('Conversation requires human handoff, skipping AI processing', [
                'conversation_id' => $conversation->id,
            ]);
            return;
        }

        // Check for opt-out keywords
        if ($this->checkOptOut($message, $conversation)) {
            return;
        }

        // Check for common commands
        if ($this->handleCommand($message, $conversation, $stateManager)) {
            return;
        }

        try {
            // Process message with AI
            $actions = $aiOrchestrator->processMessage($conversation, $message);

            Log::info('AI generated actions', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'actions_count' => count($actions),
                'actions' => $actions,
            ]);

            // Execute actions
            $results = $actionExecutor->executeActions($conversation, $actions);

            Log::info('Actions executed', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process WhatsApp message', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send fallback message
            $this->sendFallbackMessage($conversation);

            throw $e;
        }
    }

    /**
     * Check for opt-out keywords.
     */
    protected function checkOptOut(WaMessage $message, WaConversation $conversation): bool
    {
        $optOutKeywords = ['stop', 'unsubscribe', 'opt out', 'opt-out'];
        $messageText = strtolower(trim($message->body));

        foreach ($optOutKeywords as $keyword) {
            if ($messageText === $keyword) {
                // Opt out the contact
                \App\Models\WhatsApp\WaOptOut::optOut(
                    $conversation->user_id,
                    $conversation->contact->phone_e164,
                    'User requested opt-out via keyword'
                );

                // Send confirmation (this will be the last message)
                $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
                $whatsAppService->sendText(
                    $conversation->user_id,
                    $conversation->contact->phone_e164,
                    "You have been unsubscribed from automated messages. To opt back in, reply with 'START'.",
                    $conversation->id
                );

                // Close conversation
                $conversation->update(['status' => 'closed']);

                Log::info('Contact opted out', [
                    'conversation_id' => $conversation->id,
                    'phone' => $conversation->contact->phone_e164,
                ]);

                return true;
            }
        }

        // Check for opt-in keywords
        if ($messageText === 'start' || $messageText === 'subscribe') {
            \App\Models\WhatsApp\WaOptOut::optIn(
                $conversation->user_id,
                $conversation->contact->phone_e164
            );

            $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
            $whatsAppService->sendText(
                $conversation->user_id,
                $conversation->contact->phone_e164,
                "Welcome back! You've been resubscribed to our messages. How can I help you today?",
                $conversation->id
            );

            Log::info('Contact opted in', [
                'conversation_id' => $conversation->id,
                'phone' => $conversation->contact->phone_e164,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle common commands.
     */
    protected function handleCommand(
        WaMessage $message,
        WaConversation $conversation,
        ConversationStateManager $stateManager
    ): bool {
        $messageText = strtolower(trim($message->body));

        // Reset conversation
        if (in_array($messageText, ['reset', 'restart', 'start over'])) {
            $stateManager->resetToIdle($conversation);

            $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
            $whatsAppService->sendText(
                $conversation->user_id,
                $conversation->contact->phone_e164,
                "Conversation reset. How can I help you today?",
                $conversation->id
            );

            return true;
        }

        // Human agent request
        if (in_array($messageText, ['human', 'agent', 'talk to human', 'speak to agent'])) {
            $stateManager->requestHandoff($conversation, 'User requested human agent');

            $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
            $whatsAppService->sendText(
                $conversation->user_id,
                $conversation->contact->phone_e164,
                "I'm connecting you with a human agent. They'll assist you shortly.",
                $conversation->id
            );

            return true;
        }

        return false;
    }

    /**
     * Send fallback message when AI processing fails.
     */
    protected function sendFallbackMessage(WaConversation $conversation): void
    {
        try {
            $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
            $whatsAppService->sendText(
                $conversation->user_id,
                $conversation->contact->phone_e164,
                "I'm having trouble processing your request right now. A human agent will assist you shortly. Please try again later or reply with 'HUMAN' for immediate assistance.",
                $conversation->id
            );
        } catch (\Exception $e) {
            Log::error('Failed to send fallback message', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInboundWhatsAppMessageJob failed permanently', [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
