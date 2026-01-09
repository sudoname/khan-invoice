<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AIApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'customer@example.com',
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_ai_endpoints()
    {
        $endpoints = [
            ['GET', '/api/v1/ai/suggest/customers'],
            ['GET', '/api/v1/ai/suggest/items'],
            ['GET', '/api/v1/ai/suggest/due-date'],
            ['GET', '/api/v1/ai/insights'],
            ['GET', '/api/v1/ai/stats'],
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_suggests_customers_successfully()
    {
        Sanctum::actingAs($this->user);

        // Create invoice for the customer
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/v1/ai/suggest/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'count',
                    'query',
                    'duration_ms',
                ],
            ]);
    }

    /** @test */
    public function it_filters_customer_suggestions_by_query()
    {
        Sanctum::actingAs($this->user);

        $customer1 = Customer::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Acme Corporation',
        ]);

        $customer2 = Customer::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Industries',
        ]);

        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer1->id]);
        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer2->id]);

        $response = $this->getJson('/api/v1/ai/suggest/customers?q=Acme');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Acme', $data[0]['name']);
    }

    /** @test */
    public function it_returns_503_when_customer_suggestions_disabled()
    {
        Sanctum::actingAs($this->user);
        Config::set('kinvoice.ai.suggestions.enabled', false);

        $response = $this->getJson('/api/v1/ai/suggest/customers');

        $response->assertStatus(503)
            ->assertJson(['error' => 'Customer suggestions are disabled']);
    }

    /** @test */
    public function it_suggests_items_successfully()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Web Development',
            'unit_price' => 5000,
        ]);

        $response = $this->getJson('/api/v1/ai/suggest/items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'count',
                    'query',
                    'duration_ms',
                ],
            ]);
    }

    /** @test */
    public function it_suggests_items_for_specific_customer()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Customer Specific Service',
        ]);

        $response = $this->getJson("/api/v1/ai/suggest/items?customer_id={$this->customer->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_503_when_item_suggestions_disabled()
    {
        Sanctum::actingAs($this->user);
        Config::set('kinvoice.ai.suggestions.enabled', false);

        $response = $this->getJson('/api/v1/ai/suggest/items');

        $response->assertStatus(503)
            ->assertJson(['error' => 'Item suggestions are disabled']);
    }

    /** @test */
    public function it_suggests_due_date_successfully()
    {
        Sanctum::actingAs($this->user);

        // Create paid invoice with payment history
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(60),
            'due_date' => now()->subDays(30),
            'paid_at' => now()->subDays(30),
        ]);

        $response = $this->getJson("/api/v1/ai/suggest/due-date?customer_id={$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'suggested_due_date',
                    'days_from_now',
                    'confidence',
                ],
                'meta' => [
                    'customer_id',
                    'duration_ms',
                ],
            ]);
    }

    /** @test */
    public function it_returns_503_when_due_date_suggestions_disabled()
    {
        Sanctum::actingAs($this->user);
        Config::set('kinvoice.ai.suggestions.enabled', false);

        $response = $this->getJson('/api/v1/ai/suggest/due-date');

        $response->assertStatus(503)
            ->assertJson(['error' => 'Due date suggestions are disabled']);
    }

    /** @test */
    public function it_plans_reminders_successfully()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $response = $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'invoice_id',
                    'reminder_count',
                    'duration_ms',
                ],
            ]);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_invoice_reminders()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);
        $otherInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
            'customer_id' => $otherCustomer->id,
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->getJson("/api/v1/ai/reminders/plan/{$otherInvoice->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function it_returns_503_when_reminders_disabled()
    {
        Sanctum::actingAs($this->user);
        Config::set('kinvoice.ai.reminders.enabled', false);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");

        $response->assertStatus(503)
            ->assertJson(['error' => 'Payment reminders are disabled']);
    }

    /** @test */
    public function it_creates_reminder_plan_successfully()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/v1/ai/reminders/{$invoice->id}", [
            'channel' => 'email',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
                'meta' => [
                    'invoice_id',
                    'channel',
                    'reminder_count',
                    'duration_ms',
                ],
            ]);

        $this->assertDatabaseHas('payment_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_validates_reminder_channel()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->postJson("/api/v1/ai/reminders/{$invoice->id}", [
            'channel' => 'invalid-channel',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_gets_insights_successfully()
    {
        Sanctum::actingAs($this->user);

        // Create sufficient data
        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
            'paid_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/v1/ai/insights');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'duration_ms',
                ],
            ]);
    }

    /** @test */
    public function it_returns_503_when_insights_disabled()
    {
        Sanctum::actingAs($this->user);
        Config::set('kinvoice.ai.insights.enabled', false);

        $response = $this->getJson('/api/v1/ai/insights');

        $response->assertStatus(503)
            ->assertJson(['error' => 'Insights are disabled']);
    }

    /** @test */
    public function it_gets_statistics_successfully()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->getJson('/api/v1/ai/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_rate_limits_customer_suggestions()
    {
        Sanctum::actingAs($this->user);

        // Clear any existing rate limits
        RateLimiter::clear('ai_suggestions:' . $this->user->id);

        // Make requests up to the limit
        $limit = 60; // As configured in AppServiceProvider

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->getJson('/api/v1/ai/suggest/customers');
            $response->assertStatus(200);
        }

        // Next request should be rate limited
        $response = $this->getJson('/api/v1/ai/suggest/customers');
        $response->assertStatus(429);
    }

    /** @test */
    public function it_rate_limits_reminders_separately()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
        ]);

        // Clear any existing rate limits
        RateLimiter::clear('ai_reminders:' . $this->user->id);

        // Make requests up to the limit
        $limit = 10; // As configured for reminders

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");
            $response->assertStatus(200);
        }

        // Next request should be rate limited
        $response = $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");
        $response->assertStatus(429);
    }

    /** @test */
    public function it_rate_limits_insights_separately()
    {
        Sanctum::actingAs($this->user);

        // Create sufficient data for insights
        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
        ]);

        // Clear any existing rate limits
        RateLimiter::clear('ai_insights:' . $this->user->id);

        // Make requests up to the limit
        $limit = 30; // As configured for insights

        for ($i = 0; $i < $limit; $i++) {
            $response = $this->getJson('/api/v1/ai/insights');
            $response->assertStatus(200);
        }

        // Next request should be rate limited
        $response = $this->getJson('/api/v1/ai/insights');
        $response->assertStatus(429);
    }

    /** @test */
    public function it_validates_min_query_length_for_suggestions()
    {
        Sanctum::actingAs($this->user);

        $minLength = config('kinvoice.ai.suggestions.min_query_length', 2);

        // Query too short
        $response = $this->getJson('/api/v1/ai/suggest/customers?q=a');

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_customer_exists_for_item_suggestions()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai/suggest/items?customer_id=99999');

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_customer_exists_for_due_date_suggestions()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/ai/suggest/due-date?customer_id=99999');

        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_429_with_custom_error_message_for_suggestions()
    {
        Sanctum::actingAs($this->user);
        RateLimiter::clear('ai_suggestions:' . $this->user->id);

        // Hit rate limit
        for ($i = 0; $i <= 60; $i++) {
            $this->getJson('/api/v1/ai/suggest/customers');
        }

        $response = $this->getJson('/api/v1/ai/suggest/customers');

        $response->assertStatus(429)
            ->assertJson([
                'error' => 'Too many suggestion requests. Please try again later.',
            ]);
    }

    /** @test */
    public function it_returns_429_with_custom_error_message_for_reminders()
    {
        Sanctum::actingAs($this->user);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(10),
        ]);

        RateLimiter::clear('ai_reminders:' . $this->user->id);

        // Hit rate limit
        for ($i = 0; $i <= 10; $i++) {
            $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");
        }

        $response = $this->getJson("/api/v1/ai/reminders/plan/{$invoice->id}");

        $response->assertStatus(429)
            ->assertJson([
                'error' => 'Too many reminder requests. Please try again later.',
            ]);
    }

    /** @test */
    public function it_returns_429_with_custom_error_message_for_insights()
    {
        Sanctum::actingAs($this->user);

        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'paid',
        ]);

        RateLimiter::clear('ai_insights:' . $this->user->id);

        // Hit rate limit
        for ($i = 0; $i <= 30; $i++) {
            $this->getJson('/api/v1/ai/insights');
        }

        $response = $this->getJson('/api/v1/ai/insights');

        $response->assertStatus(429)
            ->assertJson([
                'error' => 'Too many insight requests. Please try again later.',
            ]);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        Sanctum::actingAs($this->user);

        // Test with non-existent invoice
        $response = $this->getJson('/api/v1/ai/reminders/plan/99999');

        $response->assertStatus(404);
    }
}
