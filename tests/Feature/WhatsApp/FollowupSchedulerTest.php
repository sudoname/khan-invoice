<?php

namespace Tests\Feature\WhatsApp;

use App\Console\Commands\RunWhatsAppFollowupsCommand;
use App\Jobs\WhatsApp\SendWhatsAppFollowupJob;
use App\Models\User;
use App\Models\Invoice;
use App\Models\BusinessProfile;
use App\Models\WhatsApp\WaAccount;
use App\Models\WhatsApp\WaContact;
use App\Models\WhatsApp\WaConversation;
use App\Models\WhatsApp\AutomationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FollowupSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WaAccount $account;
    protected AutomationRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

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

        // Create automation rule
        $this->rule = AutomationRule::create([
            'user_id' => $this->user->id,
            'name' => 'Test Follow-up Rule',
            'type' => 'unpaid_invoice_followup',
            'active' => true,
            'schedule' => [
                '0' => 60,      // 1 hour
                '1' => 1440,    // 24 hours
                '2' => 4320,    // 3 days
            ],
            'message_template' => 'Hi {{customer_name}}, reminder about Invoice {{invoice_number}} for {{currency}} {{amount}}. Pay here: {{payment_link}}',
        ]);
    }

    /** @test */
    public function it_schedules_followup_for_unpaid_invoice()
    {
        // Create WhatsApp contact and conversation
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        // Create unpaid invoice that's 2 hours old (should trigger first follow-up)
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'sent',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
            'whatsapp_followup_attempts' => 0,
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify follow-up job was dispatched
        Queue::assertPushed(SendWhatsAppFollowupJob::class, function ($job) use ($invoice) {
            return $job->invoiceId === $invoice->id && $job->attemptNumber === 1;
        });
    }

    /** @test */
    public function it_does_not_schedule_followup_for_paid_invoice()
    {
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        // Create paid invoice
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'paid',
            'created_at' => now()->subHours(2),
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify NO follow-up job was dispatched
        Queue::assertNotPushed(SendWhatsAppFollowupJob::class);
    }

    /** @test */
    public function it_schedules_multiple_followup_attempts()
    {
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        // Create unpaid invoice that's 25 hours old (should trigger second follow-up)
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'sent',
            'created_at' => now()->subHours(25),
            'updated_at' => now()->subHours(25),
            'whatsapp_followup_attempts' => 1, // First attempt already sent
            'whatsapp_last_followup_at' => now()->subHours(24),
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify second follow-up job was dispatched
        Queue::assertPushed(SendWhatsAppFollowupJob::class, function ($job) use ($invoice) {
            return $job->invoiceId === $invoice->id && $job->attemptNumber === 2;
        });
    }

    /** @test */
    public function it_does_not_send_followup_too_soon()
    {
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        // Create invoice that's only 30 minutes old (too soon for first follow-up)
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'sent',
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
            'whatsapp_followup_attempts' => 0,
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify NO follow-up job was dispatched (too soon)
        Queue::assertNotPushed(SendWhatsAppFollowupJob::class);
    }

    /** @test */
    public function it_respects_inactive_rules()
    {
        // Deactivate the rule
        $this->rule->update(['active' => false]);

        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'sent',
            'created_at' => now()->subHours(2),
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify NO follow-up job was dispatched (rule inactive)
        Queue::assertNotPushed(SendWhatsAppFollowupJob::class);
    }

    /** @test */
    public function it_renders_message_template_with_variables()
    {
        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'invoice_number' => 'INV-2024-00000123',
            'total_amount' => 50000.00,
            'currency' => 'NGN',
            'status' => 'sent',
        ]);

        $variables = [
            'invoice_number' => $invoice->invoice_number,
            'amount' => number_format($invoice->amount_due, 2),
            'currency' => $invoice->currency,
            'customer_name' => 'John Doe',
            'payment_link' => config('app.url') . '/invoice/' . $invoice->public_id,
        ];

        $message = $this->rule->renderMessage($variables);

        $this->assertStringContainsString('INV-2024-00000123', $message);
        $this->assertStringContainsString('50,000.00', $message);
        $this->assertStringContainsString('NGN', $message);
        $this->assertStringContainsString('John Doe', $message);
        $this->assertStringNotContainsString('{{invoice_number}}', $message);
    }

    /** @test */
    public function it_skips_invoices_without_whatsapp_contact()
    {
        // Create invoice without WhatsApp contact
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => null,
            'wa_contact_id' => null,
            'status' => 'sent',
            'created_at' => now()->subHours(2),
        ]);

        // Run the command
        $this->artisan(RunWhatsAppFollowupsCommand::class)
            ->assertExitCode(0);

        // Verify NO follow-up job was dispatched
        Queue::assertNotPushed(SendWhatsAppFollowupJob::class);
    }

    /** @test */
    public function followup_job_updates_invoice_tracking()
    {
        Queue::fake([]);

        $contact = WaContact::findOrCreateByPhone($this->user->id, '+2348012345678', 'John Doe');
        $conversation = WaConversation::create([
            'user_id' => $this->user->id,
            'wa_contact_id' => $contact->id,
            'status' => 'open',
            'state' => 'invoice_sent',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'wa_conversation_id' => $conversation->id,
            'wa_contact_id' => $contact->id,
            'status' => 'sent',
            'whatsapp_followup_attempts' => 0,
            'whatsapp_last_followup_at' => null,
        ]);

        // Manually dispatch the job
        $job = new SendWhatsAppFollowupJob($invoice->id, $this->rule->id, 1);
        $job->handle(app(\App\Services\WhatsApp\WhatsAppService::class));

        // Verify invoice tracking was updated
        $invoice->refresh();
        $this->assertEquals(1, $invoice->whatsapp_followup_attempts);
        $this->assertNotNull($invoice->whatsapp_last_followup_at);
    }
}
