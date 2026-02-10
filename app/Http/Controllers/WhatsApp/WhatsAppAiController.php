<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppAiOrchestrator;
use App\Services\WhatsApp\ActionExecutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppAiController extends Controller
{
    protected WhatsAppAiOrchestrator $aiOrchestrator;
    protected ActionExecutor $actionExecutor;

    public function __construct(
        WhatsAppAiOrchestrator $aiOrchestrator,
        ActionExecutor $actionExecutor
    ) {
        $this->aiOrchestrator = $aiOrchestrator;
        $this->actionExecutor = $actionExecutor;
    }

    /**
     * Test AI processing endpoint.
     */
    public function testProcess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer|exists:wa_conversations,id',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $conversationId = $request->input('conversation_id');
        $messageText = $request->input('message');

        // Get conversation
        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Create test message
        $message = \App\Models\WhatsApp\WaMessage::createInbound(
            $userId,
            $conversationId,
            $messageText,
            'text',
            'test-' . time()
        );

        // Process with AI
        $actions = $this->aiOrchestrator->processMessage($conversation, $message);

        // Execute actions
        $results = $this->actionExecutor->executeActions($conversation, $actions);

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'ai_actions' => $actions,
            'execution_results' => $results,
        ]);
    }

    /**
     * Get AI status and configuration.
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'ai_enabled' => $this->aiOrchestrator->isEnabled(),
            'model' => config('whatsapp.ai.model'),
            'provider' => config('whatsapp.ai.provider'),
        ]);
    }

    /**
     * Test AI prompt generation.
     */
    public function testPrompt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer|exists:wa_conversations,id',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $conversationId = $request->input('conversation_id');
        $messageText = $request->input('message');

        // Get conversation
        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Create test message
        $message = \App\Models\WhatsApp\WaMessage::createInbound(
            $userId,
            $conversationId,
            $messageText,
            'text',
            'test-prompt-' . time()
        );

        // Build context and prompt (use reflection to access protected methods)
        $reflection = new \ReflectionClass($this->aiOrchestrator);

        $buildContextMethod = $reflection->getMethod('buildContext');
        $buildContextMethod->setAccessible(true);
        $context = $buildContextMethod->invoke($this->aiOrchestrator, $conversation);

        $buildPromptMethod = $reflection->getMethod('buildPrompt');
        $buildPromptMethod->setAccessible(true);
        $prompt = $buildPromptMethod->invoke($this->aiOrchestrator, $conversation, $message, $context);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'context' => $context,
            'prompt' => $prompt,
        ]);
    }

    /**
     * Manually trigger action execution.
     */
    public function executeAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|integer|exists:wa_conversations,id',
            'actions' => 'required|array',
            'actions.*.type' => 'required|string',
            'actions.*.payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = auth()->id();
        $conversationId = $request->input('conversation_id');
        $actions = $request->input('actions');

        // Get conversation
        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Execute actions
        $results = $this->actionExecutor->executeActions($conversation, $actions);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'actions_count' => count($actions),
            'results' => $results,
        ]);
    }

    /**
     * Get conversation state and context.
     */
    public function getConversationState(int $conversationId)
    {
        $userId = auth()->id();

        $conversation = \App\Models\WhatsApp\WaConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'state' => $conversation->state,
            'status' => $conversation->status,
            'context' => $conversation->context,
            'human_handoff' => $conversation->human_handoff,
            'contact' => [
                'id' => $conversation->contact->id,
                'name' => $conversation->contact->name,
                'phone' => $conversation->contact->phone_e164,
            ],
        ]);
    }
}
