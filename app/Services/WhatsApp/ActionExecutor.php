<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\Lead;
use App\Models\WhatsApp\AutomationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionExecutor
{
    protected WhatsAppService $whatsAppService;
    protected ConversationStateManager $stateManager;

    public function __construct(
        WhatsAppService $whatsAppService,
        ConversationStateManager $stateManager
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->stateManager = $stateManager;
    }

    /**
     * Execute a batch of actions from AI.
     */
    public function executeActions(WaConversation $conversation, array $actions): array
    {
        $results = [];

        foreach ($actions as $action) {
            try {
                $result = $this->executeAction($conversation, $action);
                $results[] = [
                    'action' => $action,
                    'result' => $result,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                Log::error('Action execution failed', [
                    'conversation_id' => $conversation->id,
                    'action' => $action,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'action' => $action,
                    'error' => $e->getMessage(),
                    'success' => false,
                ];
            }
        }

        return $results;
    }

    /**
     * Execute a single action.
     */
    protected function executeAction(WaConversation $conversation, array $action): mixed
    {
        $type = $action['type'];
        $payload = $action['payload'];

        return match ($type) {
            'send_message' => $this->executeSendMessage($conversation, $payload),
            'collect_field' => $this->executeCollectField($conversation, $payload),
            'transition_state' => $this->executeTransitionState($conversation, $payload),
            'create_invoice' => $this->executeCreateInvoice($conversation, $payload),
            'send_invoice' => $this->executeSendInvoice($conversation, $payload),
            'handoff' => $this->executeHandoff($conversation, $payload),
            default => throw new \InvalidArgumentException("Unknown action type: {$type}"),
        };
    }

    /**
     * Execute send_message action.
     */
    protected function executeSendMessage(WaConversation $conversation, array $payload): array
    {
        $text = $payload['text'] ?? '';

        if (empty($text)) {
            throw new \InvalidArgumentException('Message text is required');
        }

        $message = $this->whatsAppService->sendText(
            $conversation->user_id,
            $conversation->contact->phone_e164,
            $text,
            $conversation->id
        );

        return [
            'message_id' => $message?->id,
            'sent' => $message !== null,
        ];
    }

    /**
     * Execute collect_field action.
     */
    protected function executeCollectField(WaConversation $conversation, array $payload): array
    {
        $field = $payload['field'] ?? '';
        $value = $payload['value'] ?? '';

        if (empty($field) || empty($value)) {
            throw new \InvalidArgumentException('Field and value are required');
        }

        // Store field in conversation context
        $this->stateManager->collectField($conversation, $field, $value);

        // If this field completes a lead or invoice, update accordingly
        if ($field === 'customer_name' || $field === 'product_interest') {
            $this->updateOrCreateLead($conversation);
        }

        return [
            'field' => $field,
            'value' => $value,
            'stored' => true,
        ];
    }

    /**
     * Execute transition_state action.
     */
    protected function executeTransitionState(WaConversation $conversation, array $payload): array
    {
        $newState = $payload['new_state'] ?? '';

        if (empty($newState)) {
            throw new \InvalidArgumentException('New state is required');
        }

        // Validate transition
        if (!$this->stateManager->canTransition($conversation->state, $newState)) {
            throw new \InvalidArgumentException(
                "Invalid state transition from {$conversation->state} to {$newState}"
            );
        }

        $this->stateManager->transitionTo($conversation, $newState);

        return [
            'old_state' => $conversation->state,
            'new_state' => $newState,
            'transitioned' => true,
        ];
    }

    /**
     * Execute create_invoice action.
     */
    protected function executeCreateInvoice(WaConversation $conversation, array $payload): array
    {
        // Validate required fields
        $customerName = $payload['customer_name'] ?? null;
        $items = $payload['items'] ?? [];

        if (empty($customerName) || empty($items)) {
            throw new \InvalidArgumentException('Customer name and items are required');
        }

        return DB::transaction(function () use ($conversation, $payload, $customerName, $items) {
            $userId = $conversation->user_id;

            // Get or create customer
            $customer = $this->getOrCreateCustomer($userId, $payload);

            // Get user's default business profile
            $businessProfile = \App\Models\BusinessProfile::where('user_id', $userId)
                ->where('is_default', true)
                ->first();

            if (!$businessProfile) {
                $businessProfile = \App\Models\BusinessProfile::where('user_id', $userId)->first();
            }

            // Create invoice
            $invoice = Invoice::create([
                'user_id' => $userId,
                'business_profile_id' => $businessProfile?->id,
                'customer_id' => $customer->id,
                'wa_conversation_id' => $conversation->id,
                'wa_contact_id' => $conversation->wa_contact_id,
                'issue_date' => now(),
                'due_date' => now()->addDays(7), // Default 7 days
                'status' => 'draft',
                'currency' => 'NGN',
                'sub_total' => 0,
                'total_amount' => 0,
                'amount_due' => 0,
                'notes' => $payload['notes'] ?? null,
                'simple_mode' => true, // WhatsApp invoices use simple mode
                'payment_enabled' => true,
            ]);

            // Create invoice items (prices set to 0 - user must set them)
            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => 0, // MUST be set by user, not AI
                    'line_total' => 0,
                ]);
            }

            // Store invoice_id in conversation context
            $conversation->setContextValue('invoice_id', $invoice->id);
            $conversation->setContextValue('invoice_status', 'draft');

            // Update lead status if exists
            $lead = Lead::where('wa_conversation_id', $conversation->id)->first();
            if ($lead) {
                $lead->updateStage('invoiced');
            }

            // Log action
            AutomationLog::logSuccess(
                null,
                $conversation->id,
                'invoice',
                $invoice->id,
                'create_invoice',
                [
                    'customer_id' => $customer->id,
                    'items_count' => count($items),
                ]
            );

            return [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $customer->id,
                'items_count' => count($items),
                'status' => 'draft',
                'created' => true,
            ];
        });
    }

    /**
     * Execute send_invoice action.
     */
    protected function executeSendInvoice(WaConversation $conversation, array $payload): array
    {
        $invoiceId = $payload['invoice_id'] ?? null;

        if (!$invoiceId) {
            throw new \InvalidArgumentException('Invoice ID is required');
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', $conversation->user_id)
            ->firstOrFail();

        // Generate public invoice link
        $publicUrl = config('app.url') . '/invoice/' . $invoice->public_id;

        // Update invoice status
        $invoice->update(['status' => 'sent']);

        // Send invoice link via WhatsApp
        $message = "📄 *Invoice #{$invoice->invoice_number}*\n\n";
        $message .= "Amount: {$invoice->currency} " . number_format($invoice->total_amount, 2) . "\n";
        $message .= "Due: " . $invoice->due_date->format('M d, Y') . "\n\n";
        $message .= "View and pay: {$publicUrl}";

        $this->whatsAppService->sendText(
            $conversation->user_id,
            $conversation->contact->phone_e164,
            $message,
            $conversation->id
        );

        // Transition state
        $this->stateManager->transitionTo($conversation, 'invoice_sent', [
            'invoice_id' => $invoice->id,
            'invoice_sent_at' => now()->toIso8601String(),
        ]);

        // Log action
        AutomationLog::logSuccess(
            null,
            $conversation->id,
            'invoice',
            $invoice->id,
            'send_invoice',
            ['public_url' => $publicUrl]
        );

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'public_url' => $publicUrl,
            'sent' => true,
        ];
    }

    /**
     * Execute handoff action.
     */
    protected function executeHandoff(WaConversation $conversation, array $payload): array
    {
        $reason = $payload['reason'] ?? 'Customer requested human assistance';

        $this->stateManager->requestHandoff($conversation, $reason);

        // Notify user (via notification or email)
        // TODO: Implement notification to business owner

        // Log action
        AutomationLog::logSuccess(
            null,
            $conversation->id,
            'conversation',
            $conversation->id,
            'handoff',
            ['reason' => $reason]
        );

        return [
            'handoff_requested' => true,
            'reason' => $reason,
        ];
    }

    /**
     * Get or create customer from payload.
     */
    protected function getOrCreateCustomer(int $userId, array $payload): Customer
    {
        $name = $payload['customer_name'];
        $email = $payload['customer_email'] ?? null;
        $phone = $payload['customer_phone'] ?? null;

        // Try to find existing customer by email or phone
        $query = Customer::where('user_id', $userId);

        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        } else {
            // Search by name as last resort
            $query->where('name', 'LIKE', "%{$name}%");
        }

        $customer = $query->first();

        if ($customer) {
            return $customer;
        }

        // Create new customer
        return Customer::create([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    /**
     * Update or create lead from conversation context.
     */
    protected function updateOrCreateLead(WaConversation $conversation): void
    {
        $context = $conversation->context ?? [];
        $customerName = $context['customer_name'] ?? null;
        $productInterest = $context['product_interest'] ?? null;

        if (!$customerName && !$productInterest) {
            return;
        }

        $lead = Lead::where('wa_conversation_id', $conversation->id)->first();

        if ($lead) {
            // Update existing lead
            $lead->update([
                'customer_name' => $customerName ?? $lead->customer_name,
                'product_interest' => $productInterest ?? $lead->product_interest,
                'stage' => 'qualified',
            ]);
        } else {
            // Create new lead
            Lead::create([
                'user_id' => $conversation->user_id,
                'wa_contact_id' => $conversation->wa_contact_id,
                'wa_conversation_id' => $conversation->id,
                'customer_name' => $customerName,
                'product_interest' => $productInterest,
                'stage' => 'new',
                'score' => 50,
            ]);
        }
    }
}
