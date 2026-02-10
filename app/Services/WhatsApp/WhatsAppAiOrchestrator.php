<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\WaMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppAiOrchestrator
{
    protected string $apiKey;
    protected string $model;
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('whatsapp.ai.enabled', true);
        $this->model = config('whatsapp.ai.model', 'gpt-4-turbo-preview');
        $this->apiKey = config('whatsapp.ai.api_key');
    }

    /**
     * Process incoming message with AI and return actions.
     */
    public function processMessage(WaConversation $conversation, WaMessage $message): array
    {
        if (!$this->enabled) {
            return $this->getFallbackResponse($conversation, $message);
        }

        // Build conversation context
        $context = $this->buildContext($conversation);

        // Build AI prompt
        $prompt = $this->buildPrompt($conversation, $message, $context);

        // Call AI provider
        $aiResponse = $this->callAiProvider($prompt);

        if (!$aiResponse) {
            Log::error('AI provider call failed', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ]);
            return $this->getFallbackResponse($conversation, $message);
        }

        // Validate and parse AI response
        $actions = $this->validateAndParseResponse($aiResponse);

        return $actions;
    }

    /**
     * Build conversation context for AI.
     */
    protected function buildContext(WaConversation $conversation): array
    {
        $user = $conversation->user;
        $contact = $conversation->contact;

        // Recent messages (last 10)
        $recentMessages = $conversation->recentMessages(10);

        $messageHistory = $recentMessages->map(function ($msg) {
            return [
                'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                'content' => $msg->body,
                'timestamp' => $msg->created_at->toIso8601String(),
            ];
        })->toArray();

        return [
            'conversation_id' => $conversation->id,
            'state' => $conversation->state,
            'context' => $conversation->context ?? [],
            'contact' => [
                'name' => $contact->name,
                'phone' => $contact->phone_e164,
            ],
            'business' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'message_history' => $messageHistory,
        ];
    }

    /**
     * Build AI prompt with action schema.
     */
    protected function buildPrompt(WaConversation $conversation, WaMessage $message, array $context): string
    {
        $systemPrompt = $this->getSystemPrompt();
        $actionSchema = $this->getActionSchema();

        $contextJson = json_encode($context, JSON_PRETTY_PRINT);
        $userMessage = $message->body;

        return <<<PROMPT
{$systemPrompt}

## Current Context
```json
{$contextJson}
```

## Action Schema
You MUST respond with a JSON array of actions following this schema:
```json
{$actionSchema}
```

## User's Message
"{$userMessage}"

## Your Response
Respond with a JSON array of actions to take. Remember:
- You cannot set prices, totals, or mark invoices as paid
- Always collect missing required fields before creating invoices
- Suggest handoff for complex requests you cannot handle
- Be conversational and helpful

PROMPT;
    }

    /**
     * Get system prompt for AI.
     */
    protected function getSystemPrompt(): string
    {
        return <<<SYSTEM
You are a WhatsApp Sales Assistant helping businesses manage customer conversations, create invoices, and track payments.

## Your Capabilities
1. Collect customer information (name, email, phone)
2. Understand product/service requests
3. Collect order details (items, quantities)
4. Create draft invoices (you suggest items and quantities, but backend sets prices)
5. Send invoice links to customers
6. Answer payment status questions
7. Escalate complex queries to human agents

## Hard Constraints
- You CANNOT set prices, unit prices, or totals directly
- You CANNOT mark invoices as paid
- You CANNOT access external systems or APIs
- You MUST collect all required fields before creating invoices
- You MUST respond with valid JSON only

## Conversation States
- idle: No active task
- collecting_lead: Gathering customer interest
- collecting_invoice: Collecting order details
- invoice_sent: Invoice has been sent, awaiting payment
- awaiting_payment: Payment initiated
- handoff: Escalated to human

## Response Format
Always respond with a JSON array of action objects. Each action has:
- type: The action type (send_message, collect_field, create_invoice, etc.)
- payload: Action-specific data

Be conversational, friendly, and helpful!
SYSTEM;
    }

    /**
     * Get action schema for AI responses.
     */
    protected function getActionSchema(): string
    {
        return <<<SCHEMA
[
  {
    "type": "send_message",
    "payload": {
      "text": "Your message to the customer"
    }
  },
  {
    "type": "collect_field",
    "payload": {
      "field": "customer_name|customer_email|customer_phone|product_interest|items",
      "value": "extracted value from user message"
    }
  },
  {
    "type": "transition_state",
    "payload": {
      "new_state": "idle|collecting_lead|collecting_invoice|invoice_sent|awaiting_payment|handoff"
    }
  },
  {
    "type": "create_invoice",
    "payload": {
      "customer_name": "string",
      "customer_email": "string|null",
      "customer_phone": "string|null",
      "items": [
        {
          "name": "string",
          "description": "string|null",
          "quantity": number
        }
      ],
      "notes": "string|null"
    }
  },
  {
    "type": "send_invoice",
    "payload": {
      "invoice_id": number
    }
  },
  {
    "type": "handoff",
    "payload": {
      "reason": "string - why human intervention is needed"
    }
  }
]
SCHEMA;
    }

    /**
     * Call AI provider (OpenAI/Anthropic).
     */
    protected function callAiProvider(string $prompt): ?string
    {
        try {
            $provider = config('whatsapp.ai.provider', 'openai');

            if ($provider === 'openai') {
                return $this->callOpenAI($prompt);
            } elseif ($provider === 'anthropic') {
                return $this->callAnthropic($prompt);
            }

            Log::warning('Unsupported AI provider', ['provider' => $provider]);
            return null;
        } catch (\Exception $e) {
            Log::error('AI provider exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Call OpenAI API.
     */
    protected function callOpenAI(string $prompt): ?string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Call Anthropic API.
     */
    protected function callAnthropic(string $prompt): ?string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1000,
            ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        Log::error('Anthropic API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Validate and parse AI response.
     */
    protected function validateAndParseResponse(string $response): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $jsonString = $this->extractJson($response);

        // Parse JSON
        $actions = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON from AI', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        // Validate each action
        $validatedActions = [];
        foreach ($actions as $action) {
            if ($this->isValidAction($action)) {
                $validatedActions[] = $action;
            } else {
                Log::warning('Invalid action from AI', ['action' => $action]);
            }
        }

        return $validatedActions;
    }

    /**
     * Extract JSON from response (handle markdown code blocks).
     */
    protected function extractJson(string $response): string
    {
        // Try to extract from markdown code block
        if (preg_match('/```(?:json)?\s*(\[.*?\])\s*```/s', $response, $matches)) {
            return $matches[1];
        }

        // Try to find raw JSON array
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            return $matches[0];
        }

        return $response;
    }

    /**
     * Validate action structure.
     */
    protected function isValidAction(array $action): bool
    {
        if (!isset($action['type']) || !isset($action['payload'])) {
            return false;
        }

        $validTypes = [
            'send_message',
            'collect_field',
            'transition_state',
            'create_invoice',
            'send_invoice',
            'handoff',
        ];

        return in_array($action['type'], $validTypes);
    }

    /**
     * Get fallback response when AI is disabled or fails.
     */
    protected function getFallbackResponse(WaConversation $conversation, WaMessage $message): array
    {
        return [
            [
                'type' => 'handoff',
                'payload' => [
                    'reason' => 'AI assistant is currently unavailable. Please contact support.',
                ],
            ],
            [
                'type' => 'send_message',
                'payload' => [
                    'text' => "I'm currently unable to process your request. A human agent will assist you shortly. Thank you for your patience!",
                ],
            ],
        ];
    }

    /**
     * Check if AI is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
