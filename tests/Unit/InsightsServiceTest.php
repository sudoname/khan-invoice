<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AI\InsightsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InsightsService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InsightsService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_returns_unavailable_when_insufficient_invoices()
    {
        // Create only 2 invoices (less than minimum required)
        Invoice::factory()->count(2)->create(['user_id' => $this->user->id]);

        $insights = $this->service->getAllInsights($this->user);

        $this->assertFalse($insights['available']);
        $this->assertArrayHasKey('reason', $insights);
    }

    /** @test */
    public function it_returns_all_insights_when_sufficient_data()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create sufficient invoices (>= 10)
        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(15),
            'paid_at' => now()->subDays(14),
        ]);

        $insights = $this->service->getAllInsights($this->user);

        $this->assertTrue($insights['available']);
        $this->assertArrayHasKey('payment_patterns', $insights);
        $this->assertArrayHasKey('late_payments', $insights);
        $this->assertArrayHasKey('revenue_trends', $insights);
        $this->assertArrayHasKey('top_customers', $insights);
        $this->assertArrayHasKey('invoice_stats', $insights);
    }

    /** @test */
    public function it_calculates_payment_patterns()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create paid invoices with varying payment durations
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(40),
            'due_date' => now()->subDays(30),
            'paid_at' => now()->subDays(25), // 5 days early
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(20),
            'paid_at' => now()->subDays(20), // On time
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(20),
            'due_date' => now()->subDays(10),
            'paid_at' => now()->subDays(5), // 5 days late
        ]);

        $patterns = $this->service->getPaymentPatterns($this->user);

        $this->assertTrue($patterns['available']);
        $this->assertArrayHasKey('average_days_to_pay', $patterns);
        $this->assertArrayHasKey('median_days_to_pay', $patterns);
        $this->assertArrayHasKey('fastest_payment_days', $patterns);
        $this->assertArrayHasKey('slowest_payment_days', $patterns);
        $this->assertArrayHasKey('early_payment_rate', $patterns);
        $this->assertArrayHasKey('on_time_payment_rate', $patterns);
        $this->assertArrayHasKey('late_payment_rate', $patterns);
    }

    /** @test */
    public function it_returns_unavailable_for_payment_patterns_with_no_paid_invoices()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'sent', // Not paid
        ]);

        $patterns = $this->service->getPaymentPatterns($this->user);

        $this->assertFalse($patterns['available']);
    }

    /** @test */
    public function it_identifies_late_payment_insights()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create late payment
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'due_date' => now()->subDays(20),
            'paid_at' => now()->subDays(5), // 15 days late
        ]);

        $latePayments = $this->service->getLatePaymentInsights($this->user);

        $this->assertTrue($latePayments['available']);
        $this->assertArrayHasKey('late_invoice_count', $latePayments);
        $this->assertArrayHasKey('average_late_days', $latePayments);
        $this->assertArrayHasKey('median_late_days', $latePayments);
        $this->assertArrayHasKey('total_late_amount', $latePayments);
        $this->assertArrayHasKey('top_late_payers', $latePayments);
        $this->assertGreaterThan(0, $latePayments['late_invoice_count']);
    }

    /** @test */
    public function it_returns_unavailable_for_late_payments_when_none_exist()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create on-time payment
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'due_date' => now()->subDays(10),
            'paid_at' => now()->subDays(10), // On time
        ]);

        $latePayments = $this->service->getLatePaymentInsights($this->user);

        $this->assertFalse($latePayments['available']);
    }

    /** @test */
    public function it_calculates_revenue_trends()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create invoices over last 3 months
        for ($i = 0; $i < 3; $i++) {
            Invoice::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'paid_at' => now()->subMonths($i),
                'total_amount' => 10000,
            ]);
        }

        $trends = $this->service->getRevenueTrends($this->user);

        $this->assertTrue($trends['available']);
        $this->assertArrayHasKey('monthly_data', $trends);
        $this->assertArrayHasKey('average_monthly_revenue', $trends);
        $this->assertArrayHasKey('highest_month', $trends);
        $this->assertArrayHasKey('lowest_month', $trends);
        $this->assertArrayHasKey('total_revenue_last_12_months', $trends);
        $this->assertArrayHasKey('growth_trend', $trends);
    }

    /** @test */
    public function it_returns_unavailable_for_revenue_trends_with_no_data()
    {
        $trends = $this->service->getRevenueTrends($this->user);

        $this->assertFalse($trends['available']);
    }

    /** @test */
    public function it_identifies_top_customers_by_revenue()
    {
        // Create 3 customers with different revenue amounts
        $customer1 = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Customer A']);
        $customer2 = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Customer B']);
        $customer3 = Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Customer C']);

        // Customer 1: 3 invoices x 10000 = 30000
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer1->id,
            'status' => 'paid',
            'total_amount' => 10000,
        ]);

        // Customer 2: 2 invoices x 15000 = 30000
        Invoice::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer2->id,
            'status' => 'paid',
            'total_amount' => 15000,
        ]);

        // Customer 3: 1 invoice x 5000 = 5000
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer3->id,
            'status' => 'paid',
            'total_amount' => 5000,
        ]);

        $topCustomers = $this->service->getTopCustomers($this->user);

        $this->assertGreaterThan(0, $topCustomers->count());
        $this->assertArrayHasKey('customer_id', $topCustomers->first());
        $this->assertArrayHasKey('customer_name', $topCustomers->first());
        $this->assertArrayHasKey('total_revenue', $topCustomers->first());

        // Top customer should be either Customer A or B (both have 30000)
        $topCustomer = $topCustomers->first();
        $this->assertContains($topCustomer['customer_name'], ['Customer A', 'Customer B']);
    }

    /** @test */
    public function it_calculates_invoice_statistics()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create invoices with different statuses
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total_amount' => 10000,
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'sent',
            'total_amount' => 5000,
            'amount_due' => 5000,
        ]);

        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 3000,
        ]);

        $stats = $this->service->getInvoiceStatistics($this->user);

        $this->assertArrayHasKey('total_invoices', $stats);
        $this->assertArrayHasKey('status_breakdown', $stats);
        $this->assertArrayHasKey('total_value', $stats);
        $this->assertArrayHasKey('paid_value', $stats);
        $this->assertArrayHasKey('outstanding_value', $stats);
        $this->assertArrayHasKey('average_invoice_value', $stats);

        $this->assertEquals(3, $stats['total_invoices']);
        $this->assertEquals(18000, $stats['total_value']);
        $this->assertEquals(10000, $stats['paid_value']);
    }

    /** @test */
    public function it_correctly_calculates_median()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create invoices with days to pay: 5, 10, 15, 20, 25
        $daysToPayValues = [5, 10, 15, 20, 25];

        foreach ($daysToPayValues as $days) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'issue_date' => now()->subDays($days + 30),
                'paid_at' => now()->subDays(30),
            ]);
        }

        $patterns = $this->service->getPaymentPatterns($this->user);

        // Median of [5, 10, 15, 20, 25] should be 15
        $this->assertEquals(15, $patterns['median_days_to_pay']);
    }

    /** @test */
    public function it_identifies_growth_trend_as_growing()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create increasing revenue trend
        // Month 1-3: 5000 each
        for ($i = 10; $i >= 8; $i--) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'paid_at' => now()->subMonths($i),
                'total_amount' => 5000,
            ]);
        }

        // Month 4-6: 10000 each (doubled)
        for ($i = 2; $i >= 0; $i--) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'paid_at' => now()->subMonths($i),
                'total_amount' => 10000,
            ]);
        }

        $trends = $this->service->getRevenueTrends($this->user);

        $this->assertEquals('growing', $trends['growth_trend']);
    }

    /** @test */
    public function it_identifies_growth_trend_as_declining()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // Create decreasing revenue trend
        // Month 1-3: 10000 each
        for ($i = 10; $i >= 8; $i--) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'paid_at' => now()->subMonths($i),
                'total_amount' => 10000,
            ]);
        }

        // Month 4-6: 5000 each (halved)
        for ($i = 2; $i >= 0; $i--) {
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'paid_at' => now()->subMonths($i),
                'total_amount' => 5000,
            ]);
        }

        $trends = $this->service->getRevenueTrends($this->user);

        $this->assertEquals('declining', $trends['growth_trend']);
    }

    /** @test */
    public function it_caches_all_insights()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'paid_at' => now()->subDays(10),
        ]);

        // First call should cache
        $insights1 = $this->service->getAllInsights($this->user);

        // Second call should return cached data
        $insights2 = $this->service->getAllInsights($this->user);

        $this->assertEquals($insights1, $insights2);
    }

    /** @test */
    public function it_clears_cache()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with("insights:all:{$this->user->id}")
            ->andReturn(true);

        $this->service->clearCache($this->user);
    }

    /** @test */
    public function it_only_shows_insights_for_user_data()
    {
        $otherUser = User::factory()->create();

        // User's invoices
        $userCustomer = Customer::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'customer_id' => $userCustomer->id,
            'status' => 'paid',
            'total_amount' => 10000,
        ]);

        // Other user's invoices
        $otherCustomer = Customer::factory()->create(['user_id' => $otherUser->id]);
        Invoice::factory()->count(20)->create([
            'user_id' => $otherUser->id,
            'customer_id' => $otherCustomer->id,
            'status' => 'paid',
            'total_amount' => 50000,
        ]);

        $insights = $this->service->getAllInsights($this->user);

        // User should only see their own data
        $this->assertEquals(10, $insights['invoice_stats']['total_invoices']);
        $this->assertEquals(100000, $insights['invoice_stats']['total_value']); // 10 * 10000
    }

    /** @test */
    public function it_calculates_early_payment_rate()
    {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        // 2 early payments
        Invoice::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'due_date' => now()->subDays(10),
            'paid_at' => now()->subDays(15), // 5 days early
        ]);

        // 3 on-time payments
        Invoice::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'due_date' => now()->subDays(10),
            'paid_at' => now()->subDays(10), // On time
        ]);

        $patterns = $this->service->getPaymentPatterns($this->user);

        // Early payment rate should be 40% (2 out of 5)
        $this->assertEquals(40.0, $patterns['early_payment_rate']);
    }

    /** @test */
    public function it_respects_top_customers_limit()
    {
        // Create more customers than the limit
        for ($i = 0; $i < 15; $i++) {
            $customer = Customer::factory()->create(['user_id' => $this->user->id]);
            Invoice::factory()->create([
                'user_id' => $this->user->id,
                'customer_id' => $customer->id,
                'status' => 'paid',
                'total_amount' => 10000,
            ]);
        }

        $topCustomers = $this->service->getTopCustomers($this->user);

        $limit = config('kinvoice.ai.insights.top_customers_limit', 10);
        $this->assertLessThanOrEqual($limit, $topCustomers->count());
    }
}
