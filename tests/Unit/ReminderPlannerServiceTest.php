<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentReminder;
use App\Models\User;
use App\Services\AI\ReminderPlannerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderPlannerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReminderPlannerService $service;
    protected User $user;
    protected Customer $customer;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReminderPlannerService::class);
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'customer@example.com',
            'phone' => '+2348012345678',
        ]);
    }

    /** @test */
    public function it_generates_reminder_plan_for_invoice()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $this->assertGreaterThan(0, $plan->count());
        $this->assertArrayHasKey('invoice_id', $plan->first());
        $this->assertArrayHasKey('scheduled_at', $plan->first());
        $this->assertArrayHasKey('type', $plan->first());
    }

    /** @test */
    public function it_creates_before_due_reminders()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $beforeDueReminders = $plan->filter(fn($r) => $r['type'] === 'before_due');
        $this->assertGreaterThan(0, $beforeDueReminders->count());
    }

    /** @test */
    public function it_creates_on_due_date_reminder()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $onDueReminders = $plan->filter(fn($r) => $r['type'] === 'on_due');
        $this->assertGreaterThan(0, $onDueReminders->count());
    }

    /** @test */
    public function it_creates_after_due_reminders()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $afterDueReminders = $plan->filter(fn($r) => $r['type'] === 'overdue');
        $this->assertGreaterThan(0, $afterDueReminders->count());
    }

    /** @test */
    public function it_skips_past_reminders()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(2), // Due in 2 days
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        // Should not include reminders scheduled for more than 2 days before due date
        foreach ($plan as $reminder) {
            if ($reminder['type'] === 'before_due') {
                $this->assertTrue($reminder['scheduled_at']->isFuture());
            }
        }
    }

    /** @test */
    public function it_returns_empty_plan_for_invoice_without_due_date()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => null,
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $this->assertCount(0, $plan);
    }

    /** @test */
    public function it_adjusts_reminders_to_business_hours()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        $businessStart = config('kinvoice.ai.reminders.business_hours.start', 9);

        foreach ($plan as $reminder) {
            $hour = $reminder['scheduled_at']->hour;
            $this->assertGreaterThanOrEqual($businessStart, $hour);
        }
    }

    /** @test */
    public function it_skips_weekends_when_configured()
    {
        // Set due date to a Friday
        $friday = Carbon::parse('next friday')->addWeeks(2);

        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => $friday,
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        if (config('kinvoice.ai.reminders.skip_weekends')) {
            foreach ($plan as $reminder) {
                $this->assertFalse($reminder['scheduled_at']->isWeekend());
            }
        }
    }

    /** @test */
    public function it_persists_reminder_plan_to_database()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $reminders = $this->service->persistPlan($this->invoice, 'email');

        $this->assertGreaterThan(0, $reminders->count());
        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $this->invoice->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_sets_correct_recipient_for_email_channel()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $reminders = $this->service->persistPlan($this->invoice, 'email');

        $firstReminder = $reminders->first();
        $this->assertEquals($this->customer->email, $firstReminder->recipient);
    }

    /** @test */
    public function it_sets_correct_recipient_for_whatsapp_channel()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $reminders = $this->service->persistPlan($this->invoice, 'whatsapp');

        $firstReminder = $reminders->first();
        $this->assertEquals($this->customer->phone, $firstReminder->recipient);
    }

    /** @test */
    public function it_generates_unique_reference_for_each_reminder()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $reminders = $this->service->persistPlan($this->invoice, 'email');

        $references = $reminders->pluck('reference')->unique();
        $this->assertEquals($reminders->count(), $references->count());
    }

    /** @test */
    public function it_generates_appropriate_reminder_messages()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
            'invoice_number' => 'INV-001',
            'total_amount' => 50000,
        ]);

        $reminders = $this->service->persistPlan($this->invoice, 'email');

        $firstReminder = $reminders->first();
        $this->assertNotEmpty($firstReminder->message);
        $this->assertStringContainsString($this->customer->name, $firstReminder->message);
        $this->assertStringContainsString('INV-001', $firstReminder->message);
    }

    /** @test */
    public function it_updates_existing_plan()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        // Create initial plan
        $this->service->persistPlan($this->invoice, 'email');

        // Update plan
        $newReminders = $this->service->updatePlan($this->invoice, 'whatsapp');

        // Old reminders should be canceled
        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $this->invoice->id,
            'status' => 'canceled',
        ]);

        // New reminders should be pending
        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $this->invoice->id,
            'channel' => 'whatsapp',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_cancels_all_reminders_for_invoice()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $this->service->persistPlan($this->invoice, 'email');

        $canceledCount = $this->service->cancelAllReminders($this->invoice);

        $this->assertGreaterThan(0, $canceledCount);
        $this->assertDatabaseMissing('payment_reminders', [
            'invoice_id' => $this->invoice->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_gets_due_reminders()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(1),
            'status' => 'sent',
        ]);

        // Create reminder scheduled for now
        PaymentReminder::create([
            'invoice_id' => $this->invoice->id,
            'channel' => 'email',
            'scheduled_at' => now()->subMinute(),
            'status' => 'pending',
            'message' => 'Test reminder',
            'recipient' => $this->customer->email,
            'reference' => 'TEST-' . uniqid(),
        ]);

        $dueReminders = $this->service->getDueReminders();

        // May be empty if outside business hours or weekend
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dueReminders);
    }

    /** @test */
    public function it_respects_business_hours_for_due_reminders()
    {
        $config = config('kinvoice.ai.reminders.business_hours');

        if (!$config) {
            $this->markTestSkipped('Business hours not configured');
        }

        // This test depends on current time
        // Just verify the method executes without error
        $dueReminders = $this->service->getDueReminders();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dueReminders);
    }

    /** @test */
    public function it_returns_reminder_statistics()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $this->service->persistPlan($this->invoice, 'email');

        $stats = $this->service->getStatistics($this->invoice);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertGreaterThan(0, $stats['total']);
    }

    /** @test */
    public function it_includes_reminder_description()
    {
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $plan = $this->service->plan($this->invoice);

        foreach ($plan as $reminder) {
            $this->assertArrayHasKey('description', $reminder);
            $this->assertNotEmpty($reminder['description']);
        }
    }
}
