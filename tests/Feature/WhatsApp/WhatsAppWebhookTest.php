<?php

namespace Tests\Feature\WhatsApp;

use App\Models\User;
use App\Models\WhatsApp\WaAccount;
use App\Models\WhatsApp\WaContact;
use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\WaMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WaAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and WhatsApp account
        $this->user = User::factory()->create();

        $this->account = WaAccount::create([
            'user_id' => $this->user->id,
            'provider' => 'meta',
            'phone_number_id' => '123456789',
            'waba_id' => 'waba_123',
            'access_token' => 'test_token',
            'verify_token' => 'test_verify_token',
            'status' => 'connected',
        ]);

        // Set config values
        config(['whatsapp.meta.verify_token' => 'test_verify_token']);
    }

    /** @test */
    public function it_verifies_webhook_endpoint_with_correct_token()
    {
        $response = $this->get(route('whatsapp.webhook.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'test_verify_token',
            'hub.challenge' => 'test_challenge_123',
        ]));

        $response->assertStatus(200);
        $response->assertSee('test_challenge_123');
    }

    /** @test */
    public function it_rejects_webhook_verification_with_incorrect_token()
    {
        $response = $this->get(route('whatsapp.webhook.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong_token',
            'hub.challenge' => 'test_challenge_123',
        ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_receives_and_processes_incoming_message()
    {
        $payload = $this->getTestWebhookPayload();

        $response = $this->postJson(route('whatsapp.webhook.receive'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);

        // Verify contact was created
        $this->assertDatabaseHas('wa_contacts', [
            'user_id' => $this->user->id,
            'phone_e164' => '+2348012345678',
        ]);

        // Verify conversation was created
        $contact = WaContact::where('phone_e164', '+2348012345678')->first();
        $this->assertDatabaseHas('wa_conversations', [
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
        ]);

        // Verify message was created
        $this->assertDatabaseHas('wa_messages', [
            'provider_message_id' => 'wamid.test123',
            'direction' => 'inbound',
            'body' => 'Hello, I need an invoice',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_message_processing()
    {
        $payload = $this->getTestWebhookPayload();

        // First webhook
        $this->postJson(route('whatsapp.webhook.receive'), $payload);

        // Verify message was created
        $this->assertDatabaseCount('wa_messages', 1);

        // Second webhook with same message ID (duplicate)
        $this->postJson(route('whatsapp.webhook.receive'), $payload);

        // Should still only have one message (idempotency)
        $this->assertDatabaseCount('wa_messages', 1);
    }

    /** @test */
    public function it_updates_message_status_on_delivery()
    {
        // Create a message first
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'Test User');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'idle',
        ]);

        $message = WaMessage::createOutbound(
            $this->user->id,
            $conversation->id,
            'Test message'
        );

        $message->update(['provider_message_id' => 'wamid.test456']);

        // Send status update webhook
        $statusPayload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => '123456789',
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.test456',
                                        'status' => 'delivered',
                                        'timestamp' => time(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('whatsapp.webhook.receive'), $statusPayload);

        // Verify message status was updated
        $message->refresh();
        $this->assertEquals('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    /** @test */
    public function it_parses_webhook_payload_correctly()
    {
        $payload = $this->getTestWebhookPayload();

        $service = app(\App\Services\WhatsApp\WhatsAppService::class);
        $parsed = $service->parseWebhookPayload($payload);

        $this->assertCount(1, $parsed['messages']);
        $this->assertEquals('2348012345678', $parsed['messages'][0]['from']);
        $this->assertEquals('Hello, I need an invoice', $parsed['messages'][0]['text']);
        $this->assertEquals('wamid.test123', $parsed['messages'][0]['id']);
    }

    /**
     * Get test webhook payload from Meta.
     */
    protected function getTestWebhookPayload(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '15551234567',
                                    'phone_number_id' => '123456789',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => [
                                            'name' => 'Test User',
                                        ],
                                        'wa_id' => '2348012345678',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '2348012345678',
                                        'id' => 'wamid.test123',
                                        'timestamp' => time(),
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, I need an invoice',
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];
    }
}
