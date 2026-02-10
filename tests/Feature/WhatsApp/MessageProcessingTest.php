<?php

namespace Tests\Feature\WhatsApp;

use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\BusinessProfile;
use App\Models\WhatsApp\WaAccount;
use App\Models\WhatsApp\WaContact;
use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\WaMessage;
use App\Services\WhatsApp\ActionExecutor;
use App\Services\WhatsApp\ConversationStateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WaAccount $account;
    protected WaContact $contact;
    protected WaConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create business profile
        BusinessProfile::create([
            'user_id' => $this->user->id,
            'business_name' => 'Test Business',
            'is_default' => true,
        ]);

        // Create WhatsApp account
        $this->account = WaAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'meta',
            'phone_number_id' => '123456789',
            'waba_id' => 'waba_123',
            'access_token' => 'test_token',
            'verify_token' => 'test_verify_token',
            'status' => 'connected',
        ]);

        // Create contact and conversation
        $this->contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');

        $this->conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $this->contact->id,
            'status' => 'open',
            'state' => 'idle',
        ]);
    }

    /** @test */
    public function it_collects_customer_information()
    {
        $stateManager = app(ConversationStateManager::class);
        $actionExecutor = app(ActionExecutor::class);

        // Collect customer name
        $actions = [
            [
                'type' => 'collect_field',
                'payload' => [
                    'field' => 'customer_name',
                    'value' => 'John Doe',
                ],
            ],
        ];

        $results = $actionExecutor->executeActions($this->conversation, $actions);

        $this->conversation->refresh();
        $this->assertEquals('John Doe', $this->conversation->context['customer_name']);
        $this->assertTrue($results[0]['success']);
    }

    /** @test */
    public function it_transitions_conversation_state()
    {
        $stateManager = app(ConversationStateManager::class);

        // Initial state is idle
        $this->assertEquals('idle', $this->conversation->state);

        // Transition to collecting invoice
        $stateManager->transitionTo($this->conversation, 'collecting_invoice');

        $this->conversation->refresh();
        $this->assertEquals('collecting_invoice', $this->conversation->state);
    }

    /** @test */
    public function it_validates_state_transitions()
    {
        $stateManager = app(ConversationStateManager::class);

        // Valid transition: idle -> collecting_invoice
        $this->assertTrue(
            $stateManager->canTransition('idle', 'collecting_invoice')
        );

        // Invalid transition: idle -> awaiting_payment (skipping steps)
        $this->assertFalse(
            $stateManager->canTransition('idle', 'awaiting_payment')
        );
    }

    /** @test */
    public function it_creates_invoice_with_collected_data()
    {
        $actionExecutor = app(ActionExecutor::class);

        // Prepare collected data
        $actions = [
            [
                'type' => 'create_invoice',
                'payload' => [
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'customer_phone' => '+2348012345678',
                    'items' => [
                        [
                            'name' => 'Product A',
                            'description' => 'Test product',
                            'quantity' => 2,
                        ],
                        [
                            'name' => 'Product B',
                            'quantity' => 1,
                        ],
                    ],
                    'notes' => 'Created via WhatsApp',
                ],
            ],
        ];

        $results = $actionExecutor->executeActions($this->conversation, $actions);

        $this->assertTrue($results[0]['success']);
        $this->assertArrayHasKey('invoice_id', $results[0]['result']);

        // Verify invoice was created
        $invoice = Invoice::find($results[0]['result']['invoice_id']);
        $this->assertNotNull($invoice);
        $this->assertEquals($this->user->id, $invoice->user_id);
        $this->assertEquals('draft', $invoice->status);
        $this->assertEquals($this->conversation->id, $invoice->wa_conversation_id);
        $this->assertTrue($invoice->simple_mode);

        // Verify customer was created or found
        $customer = Customer::find($invoice->customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals('John Doe', $customer->name);

        // Verify invoice items were created
        $this->assertCount(2, $invoice->items);
        $this->assertEquals('Product A', $invoice->items[0]->product_name);
        $this->assertEquals(2, $invoice->items[0]->quantity);
        $this->assertEquals(0, $invoice->items[0]->unit_price); // AI cannot set prices
    }

    /** @test */
    public function it_checks_required_fields_for_state()
    {
        $stateManager = app(ConversationStateManager::class);

        // Transition to collecting invoice state
        $this->conversation->updateState('collecting_invoice');

        // Check required fields (customer_name and items)
        $this->assertFalse($stateManager->hasRequiredFields($this->conversation));

        // Collect customer name
        $stateManager->collectField($this->conversation, 'customer_name', 'John Doe');

        // Still missing items
        $this->assertFalse($stateManager->hasRequiredFields($this->conversation));

        // Collect items
        $stateManager->collectField($this->conversation, 'items', [
            ['name' => 'Product A', 'quantity' => 1],
        ]);

        // Now all required fields are present
        $this->assertTrue($stateManager->hasRequiredFields($this->conversation));
    }

    /** @test */
    public function it_parses_items_from_message()
    {
        $stateManager = app(ConversationStateManager::class);

        // Test parsing "2 bags of rice"
        $items = $stateManager->parseItemsFromMessage('2 bags of rice');

        $this->assertCount(1, $items);
        $this->assertEquals('bags of rice', $items[0]['name']);
        $this->assertEquals(2, $items[0]['quantity']);

        // Test parsing "3 laptops and 5 mice"
        $items = $stateManager->parseItemsFromMessage('3 laptops and 5 mice');

        $this->assertCount(2, $items);
        $this->assertEquals('laptops', $items[0]['name']);
        $this->assertEquals(3, $items[0]['quantity']);
        $this->assertEquals('mice', $items[1]['name']);
        $this->assertEquals(5, $items[1]['quantity']);
    }

    /** @test */
    public function it_handles_handoff_action()
    {
        $actionExecutor = app(ActionExecutor::class);

        $actions = [
            [
                'type' => 'handoff',
                'payload' => [
                    'reason' => 'Customer needs complex pricing',
                ],
            ],
        ];

        $results = $actionExecutor->executeActions($this->conversation, $actions);

        $this->assertTrue($results[0]['success']);

        $this->conversation->refresh();
        $this->assertTrue($this->conversation->human_handoff);
        $this->assertEquals('Customer needs complex pricing', $this->conversation->handoff_reason);
        $this->assertEquals('handoff', $this->conversation->state);
    }

    /** @test */
    public function it_sends_invoice_link()
    {
        $actionExecutor = app(ActionExecutor::class);

        // First create an invoice
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $this->conversation->id,
            'wa_contact_id' => $this->contact->id,
            'status' => 'draft',
        ]);

        // Send invoice
        $actions = [
            [
                'type' => 'send_invoice',
                'payload' => [
                    'invoice_id' => $invoice->id,
                ],
            ],
        ];

        $results = $actionExecutor->executeActions($this->conversation, $actions);

        $this->assertTrue($results[0]['success']);
        $this->assertArrayHasKey('public_url', $results[0]['result']);

        // Verify invoice status was updated
        $invoice->refresh();
        $this->assertEquals('sent', $invoice->status);

        // Verify conversation state changed
        $this->conversation->refresh();
        $this->assertEquals('invoice_sent', $this->conversation->state);

        // Verify message was queued
        $this->assertDatabaseHas('wa_messages', [
            'wa_conversation_id' => $this->conversation->id,
            'direction' => 'outbound',
        ]);
    }
}
