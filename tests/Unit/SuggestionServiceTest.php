<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\AI\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SuggestionService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SuggestionService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_suggests_customers_based_on_recent_invoices()
    {
        // Create customers with different invoice recency
        $recentCustomer = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Recent Customer']);
        $oldCustomer = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Old Customer']);

        // Recent customer has invoice from 2 days ago
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $recentCustomer->id,
            'issue_date' => now()->subDays(2),
        ]);

        // Old customer has invoice from 60 days ago
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $oldCustomer->id,
            'issue_date' => now()->subDays(60),
        ]);

        $suggestions = $this->service->suggestCustomers($this->user);

        $this->assertCount(2, $suggestions);
        // Recent customer should be ranked higher
        $this->assertEquals($recentCustomer->id, $suggestions->first()['id']);
    }

    /** @test */
    public function it_suggests_customers_based_on_frequency()
    {
        $frequentCustomer = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Frequent Customer']);
        $infrequentCustomer = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Infrequent Customer']);

        // Frequent customer has 5 invoices
        Invoice::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'customer_id' => $frequentCustomer->id,
            'issue_date' => now()->subDays(30),
        ]);

        // Infrequent customer has 1 invoice
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $infrequentCustomer->id,
            'issue_date' => now()->subDays(30),
        ]);

        $suggestions = $this->service->suggestCustomers($this->user);

        $this->assertCount(2, $suggestions);
        // Frequent customer should be ranked higher
        $this->assertEquals($frequentCustomer->id, $suggestions->first()['id']);
    }

    /** @test */
    public function it_filters_customer_suggestions_by_query()
    {
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Acme Corporation']);
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Beta Industries']);

        // Create invoices for both
        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => 1]);
        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => 2]);

        $suggestions = $this->service->suggestCustomers($this->user, 'Acme');

        $this->assertCount(1, $suggestions);
        $this->assertStringContainsString('Acme', $suggestions->first()['name']);
    }

    /** @test */
    public function it_respects_max_results_limit()
    {
        // Create 15 customers with invoices
        $customers = Customer::factory()->count(15)->create(['user_id' => $this->user->id]);

        foreach ($customers as $customer) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
            ]);
        }

        $suggestions = $this->service->suggestCustomers($this->user);

        // Should return max 10 (default config)
        $this->assertLessThanOrEqual(10, $suggestions->count());
    }

    /** @test */
    public function it_caches_customer_suggestions()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([['id' => $customer->id, 'name' => $customer->name]]));

        $this->service->suggestCustomers($this->user);
    }

    /** @test */
    public function it_suggests_items_based_on_user_history()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);

        // Create invoice items
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Web Development',
            'unit_price' => 5000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Logo Design',
            'unit_price' => 2000,
        ]);

        $suggestions = $this->service->suggestItems($this->user);

        $this->assertGreaterThan(0, $suggestions->count());
        $this->assertArrayHasKey('description', $suggestions->first());
        $this->assertArrayHasKey('unit_price', $suggestions->first());
    }

    /** @test */
    public function it_filters_item_suggestions_by_query()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Web Development',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Mobile App Development',
        ]);

        $suggestions = $this->service->suggestItems($this->user, 'Web');

        $this->assertGreaterThan(0, $suggestions->count());
        $this->assertStringContainsString('Web', $suggestions->first()['description']);
    }

    /** @test */
    public function it_suggests_items_specific_to_customer()
    {
        $customer1 = Customer::factory()->create(['user_id' => $this->user->id]);
        $customer2 = Customer::factory()->create(['user_id' => $this->user->id]);

        $invoice1 = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer1->id]);
        $invoice2 = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer2->id]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice1->id,
            'description' => 'Customer 1 Service',
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice2->id,
            'description' => 'Customer 2 Service',
        ]);

        $suggestions = $this->service->suggestItems($this->user, '', $customer1->id);

        $this->assertGreaterThan(0, $suggestions->count());
        // Should prioritize customer 1's items
        $firstSuggestion = $suggestions->first();
        $this->assertStringContainsString('Customer 1', $firstSuggestion['description']);
    }

    /** @test */
    public function it_suggests_due_date_based_on_payment_history()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create paid invoices with consistent payment patterns
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(60),
            'due_date' => now()->subDays(30),
            'paid_at' => now()->subDays(30), // Paid on time
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(90),
            'due_date' => now()->subDays(60),
            'paid_at' => now()->subDays(60), // Paid on time
        ]);

        $suggestion = $this->service->suggestDueDate($this->user, $customer->id);

        $this->assertArrayHasKey('suggested_due_date', $suggestion);
        $this->assertArrayHasKey('days_from_now', $suggestion);
        $this->assertArrayHasKey('confidence', $suggestion);
    }

    /** @test */
    public function it_returns_default_due_date_when_no_history()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $suggestion = $this->service->suggestDueDate($this->user, $customer->id);

        $this->assertArrayHasKey('suggested_due_date', $suggestion);
        $this->assertEquals('low', $suggestion['confidence']);
    }

    /** @test */
    public function it_clears_cache_for_user()
    {
        Cache::shouldReceive('forget')
            ->times(3) // customers, items, due_date caches
            ->andReturn(true);

        $this->service->clearCache($this->user);
    }

    /** @test */
    public function it_returns_statistics()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $customer->id]);

        InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $stats = $this->service->getStatistics($this->user);

        $this->assertArrayHasKey('total_customers', $stats);
        $this->assertArrayHasKey('total_invoices', $stats);
        $this->assertArrayHasKey('unique_items', $stats);
        $this->assertArrayHasKey('avg_invoice_value', $stats);
    }

    /** @test */
    public function it_only_suggests_customers_belonging_to_user()
    {
        $otherUser = User::factory()->create();

        // User's customer
        $userCustomer = Customer::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $userCustomer->id]);

        // Other user's customer
        $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);
        Invoice::factory()->create(['user_id' => $otherUser->id, 'customer_id' => $otherCustomer->id]);

        $suggestions = $this->service->suggestCustomers($this->user);

        $this->assertCount(1, $suggestions);
        $this->assertEquals($userCustomer->id, $suggestions->first()['id']);
    }

    /** @test */
    public function it_only_suggests_items_from_user_invoices()
    {
        $otherUser = User::factory()->create();

        // User's invoice
        $userCustomer = Customer::factory()->create(['user_id' => $this->user->id]);
        $userInvoice = Invoice::factory()->create(['user_id' => $this->user->id, 'customer_id' => $userCustomer->id]);
        InvoiceItem::factory()->create([
            'invoice_id' => $userInvoice->id,
            'description' => 'User Service',
        ]);

        // Other user's invoice
        $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);
        $otherInvoice = Invoice::factory()->create(['user_id' => $otherUser->id, 'customer_id' => $otherCustomer->id]);
        InvoiceItem::factory()->create([
            'invoice_id' => $otherInvoice->id,
            'description' => 'Other Service',
        ]);

        $suggestions = $this->service->suggestItems($this->user);

        $this->assertGreaterThan(0, $suggestions->count());

        foreach ($suggestions as $suggestion) {
            $this->assertStringContainsString('User', $suggestion['description']);
        }
    }
}
