<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WaConversation;

class ConversationStateManager
{
    /**
     * Available conversation states.
     */
    const STATE_IDLE = 'idle';
    const STATE_COLLECTING_LEAD = 'collecting_lead';
    const STATE_COLLECTING_INVOICE = 'collecting_invoice';
    const STATE_INVOICE_SENT = 'invoice_sent';
    const STATE_AWAITING_PAYMENT = 'awaiting_payment';
    const STATE_HANDOFF = 'handoff';

    /**
     * Required fields for each state.
     */
    protected array $requiredFields = [
        self::STATE_COLLECTING_LEAD => ['customer_name', 'product_interest'],
        self::STATE_COLLECTING_INVOICE => ['customer_name', 'items'],
    ];

    /**
     * Transition conversation to a new state.
     */
    public function transitionTo(WaConversation $conversation, string $newState, ?array $contextMerge = null): void
    {
        $conversation->updateState($newState, $contextMerge);
    }

    /**
     * Check if all required fields are collected for current state.
     */
    public function hasRequiredFields(WaConversation $conversation): bool
    {
        $state = $conversation->state;
        $context = $conversation->context ?? [];

        if (!isset($this->requiredFields[$state])) {
            return true; // No required fields for this state
        }

        foreach ($this->requiredFields[$state] as $field) {
            if (empty($context[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing fields for current state.
     */
    public function getMissingFields(WaConversation $conversation): array
    {
        $state = $conversation->state;
        $context = $conversation->context ?? [];
        $missing = [];

        if (!isset($this->requiredFields[$state])) {
            return $missing;
        }

        foreach ($this->requiredFields[$state] as $field) {
            if (empty($context[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Collect a field value into context.
     */
    public function collectField(WaConversation $conversation, string $field, $value): void
    {
        $conversation->setContextValue($field, $value);
    }

    /**
     * Get next question for missing fields.
     */
    public function getNextQuestion(WaConversation $conversation): ?string
    {
        $missing = $this->getMissingFields($conversation);

        if (empty($missing)) {
            return null;
        }

        $field = $missing[0];

        return match ($field) {
            'customer_name' => "What's your name?",
            'customer_phone' => "What's your phone number?",
            'customer_email' => "What's your email address?",
            'product_interest' => "What product or service are you interested in?",
            'items' => "What items would you like to order? Please describe them.",
            'quantity' => "How many units?",
            'delivery_location' => "Where should we deliver to?",
            default => "Could you provide: {$field}?",
        };
    }

    /**
     * Reset conversation to idle.
     */
    public function resetToIdle(WaConversation $conversation): void
    {
        $conversation->update([
            'state' => self::STATE_IDLE,
            'context' => [],
            'last_intent' => null,
        ]);
    }

    /**
     * Mark conversation for human handoff.
     */
    public function requestHandoff(WaConversation $conversation, string $reason): void
    {
        $conversation->requestHandoff($reason);
    }

    /**
     * Check if conversation is idle.
     */
    public function isIdle(WaConversation $conversation): bool
    {
        return $conversation->state === self::STATE_IDLE;
    }

    /**
     * Check if conversation needs human intervention.
     */
    public function needsHumanHandoff(WaConversation $conversation): bool
    {
        return $conversation->human_handoff || $conversation->state === self::STATE_HANDOFF;
    }

    /**
     * Extract items from user message.
     */
    public function parseItemsFromMessage(string $message): array
    {
        $items = [];

        // Simple parsing - look for patterns like:
        // "2 bags of rice" or "rice x2" or "3 containers"
        if (preg_match_all('/(\d+)\s*(?:x\s*)?([a-z\s]+)/i', $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $items[] = [
                    'name' => trim($match[2]),
                    'quantity' => (int) $match[1],
                    'unit_price' => null, // To be filled by AI or user
                ];
            }
        }

        // Fallback: treat whole message as single item
        if (empty($items)) {
            $items[] = [
                'name' => trim($message),
                'quantity' => 1,
                'unit_price' => null,
            ];
        }

        return $items;
    }

    /**
     * Validate state transition.
     */
    public function canTransition(string $fromState, string $toState): bool
    {
        $allowedTransitions = [
            self::STATE_IDLE => [
                self::STATE_COLLECTING_LEAD,
                self::STATE_COLLECTING_INVOICE,
                self::STATE_HANDOFF,
            ],
            self::STATE_COLLECTING_LEAD => [
                self::STATE_IDLE,
                self::STATE_COLLECTING_INVOICE,
                self::STATE_HANDOFF,
            ],
            self::STATE_COLLECTING_INVOICE => [
                self::STATE_INVOICE_SENT,
                self::STATE_IDLE,
                self::STATE_HANDOFF,
            ],
            self::STATE_INVOICE_SENT => [
                self::STATE_AWAITING_PAYMENT,
                self::STATE_IDLE,
            ],
            self::STATE_AWAITING_PAYMENT => [
                self::STATE_IDLE,
            ],
            self::STATE_HANDOFF => [
                self::STATE_IDLE,
                self::STATE_COLLECTING_INVOICE,
            ],
        ];

        return in_array($toState, $allowedTransitions[$fromState] ?? []);
    }
}
