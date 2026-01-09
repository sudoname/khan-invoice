<?php

namespace App\Services\AI;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InsightsService
{
    /**
     * Get all insights for a user
     *
     * @param User $user
     * @return array
     */
    public function getAllInsights(User $user): array
    {
        $config = config('kinvoice.ai.insights');
        $cacheKey = "insights:all:{$user->id}";

        return Cache::remember($cacheKey, $config['cache_ttl'], function () use ($user, $config) {
            $invoiceCount = Invoice::where('user_id', $user->id)->count();

            if ($invoiceCount < $config['min_invoices_for_insights']) {
                return [
                    'available' => false,
                    'reason' => "Need at least {$config['min_invoices_for_insights']} invoices for insights",
                    'current_invoice_count' => $invoiceCount,
                ];
            }

            return [
                'available' => true,
                'payment_patterns' => $this->getPaymentPatterns($user),
                'late_payments' => $this->getLatePaymentInsights($user),
                'revenue_trends' => $this->getRevenueTrends($user),
                'top_customers' => $this->getTopCustomers($user),
                'invoice_stats' => $this->getInvoiceStatistics($user),
            ];
        });
    }

    /**
     * Get payment pattern insights
     *
     * @param User $user
     * @return array
     */
    public function getPaymentPatterns(User $user): array
    {
        $paidInvoices = Invoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereNotNull('issue_date')
            ->whereNotNull('paid_at')
            ->get();

        if ($paidInvoices->isEmpty()) {
            return [
                'available' => false,
                'message' => 'No paid invoices to analyze',
            ];
        }

        // Calculate days to payment
        $paymentDurations = $paidInvoices->map(function ($invoice) {
            return [
                'days' => $invoice->issue_date->diffInDays($invoice->paid_at),
                'invoice_id' => $invoice->id,
            ];
        });

        $durations = $paymentDurations->pluck('days');

        return [
            'available' => true,
            'average_days_to_pay' => round($durations->avg(), 1),
            'median_days_to_pay' => $this->calculateMedian($durations),
            'fastest_payment_days' => $durations->min(),
            'slowest_payment_days' => $durations->max(),
            'total_paid_invoices' => $paidInvoices->count(),
            'early_payment_rate' => $this->calculateEarlyPaymentRate($paidInvoices),
            'on_time_payment_rate' => $this->calculateOnTimePaymentRate($paidInvoices),
            'late_payment_rate' => $this->calculateLatePaymentRate($paidInvoices),
        ];
    }

    /**
     * Get late payment insights
     *
     * @param User $user
     * @return array
     */
    public function getLatePaymentInsights(User $user): array
    {
        $config = config('kinvoice.ai.insights');
        $threshold = $config['late_payment_threshold_days'];

        $lateInvoices = Invoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereNotNull('due_date')
            ->whereNotNull('paid_at')
            ->get()
            ->filter(function ($invoice) use ($threshold) {
                return $invoice->paid_at->gt($invoice->due_date->addDays($threshold));
            });

        if ($lateInvoices->isEmpty()) {
            return [
                'available' => false,
                'message' => 'No late payments found',
            ];
        }

        // Calculate average lateness
        $lateDays = $lateInvoices->map(function ($invoice) {
            return $invoice->due_date->diffInDays($invoice->paid_at);
        });

        // Group by customer
        $lateByCustomer = $lateInvoices->groupBy('customer_id')
            ->map(function ($invoices, $customerId) {
                $customer = Customer::find($customerId);
                return [
                    'customer_id' => $customerId,
                    'customer_name' => $customer?->name ?? 'Unknown',
                    'late_invoice_count' => $invoices->count(),
                    'total_late_amount' => $invoices->sum('total_amount'),
                ];
            })
            ->sortByDesc('late_invoice_count')
            ->take($config['top_late_payers_limit'])
            ->values();

        return [
            'available' => true,
            'late_invoice_count' => $lateInvoices->count(),
            'average_late_days' => round($lateDays->avg(), 1),
            'median_late_days' => $this->calculateMedian($lateDays),
            'total_late_amount' => $lateInvoices->sum('total_amount'),
            'top_late_payers' => $lateByCustomer,
        ];
    }

    /**
     * Get revenue trends
     *
     * @param User $user
     * @return array
     */
    public function getRevenueTrends(User $user): array
    {
        // Last 12 months
        $startDate = now()->subMonths(12)->startOfMonth();

        $monthlyRevenue = Invoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->where('paid_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        if ($monthlyRevenue->isEmpty()) {
            return [
                'available' => false,
                'message' => 'No revenue data available',
            ];
        }

        $revenues = $monthlyRevenue->pluck('total_revenue');

        return [
            'available' => true,
            'monthly_data' => $monthlyRevenue,
            'average_monthly_revenue' => round($revenues->avg(), 2),
            'highest_month' => $monthlyRevenue->sortByDesc('total_revenue')->first(),
            'lowest_month' => $monthlyRevenue->sortBy('total_revenue')->first(),
            'total_revenue_last_12_months' => $revenues->sum(),
            'growth_trend' => $this->calculateGrowthTrend($monthlyRevenue),
        ];
    }

    /**
     * Get top customers by revenue
     *
     * @param User $user
     * @return Collection
     */
    public function getTopCustomers(User $user): Collection
    {
        $config = config('kinvoice.ai.insights');

        return Customer::where('user_id', $user->id)
            ->select('customers.*')
            ->selectSub(function ($query) {
                $query->from('invoices')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->where('status', 'paid')
                    ->selectRaw('COUNT(*)');
            }, 'paid_invoice_count')
            ->selectSub(function ($query) {
                $query->from('invoices')
                    ->whereColumn('invoices.customer_id', 'customers.id')
                    ->where('status', 'paid')
                    ->selectRaw('SUM(total_amount)');
            }, 'total_revenue')
            ->having('paid_invoice_count', '>', 0)
            ->orderByDesc('total_revenue')
            ->limit($config['top_customers_limit'])
            ->get()
            ->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'paid_invoice_count' => $customer->paid_invoice_count,
                    'total_revenue' => round($customer->total_revenue, 2),
                ];
            });
    }

    /**
     * Get invoice statistics
     *
     * @param User $user
     * @return array
     */
    public function getInvoiceStatistics(User $user): array
    {
        $invoices = Invoice::where('user_id', $user->id)->get();

        $statusBreakdown = $invoices->groupBy('status')->map->count();

        return [
            'total_invoices' => $invoices->count(),
            'status_breakdown' => $statusBreakdown,
            'total_value' => $invoices->sum('total_amount'),
            'paid_value' => $invoices->where('status', 'paid')->sum('total_amount'),
            'outstanding_value' => $invoices->whereIn('status', ['sent', 'overdue', 'partially_paid'])->sum('amount_due'),
            'average_invoice_value' => round($invoices->avg('total_amount'), 2),
        ];
    }

    /**
     * Calculate median of a collection
     *
     * @param Collection $values
     * @return float
     */
    protected function calculateMedian(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        if ($count % 2 === 0) {
            return ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
        }

        return $sorted[floor($count / 2)];
    }

    /**
     * Calculate early payment rate
     *
     * @param Collection $paidInvoices
     * @return float
     */
    protected function calculateEarlyPaymentRate(Collection $paidInvoices): float
    {
        $config = config('kinvoice.ai.insights');
        $threshold = $config['early_payment_threshold_days'];

        $earlyCount = $paidInvoices->filter(function ($invoice) use ($threshold) {
            if (!$invoice->due_date || !$invoice->paid_at) {
                return false;
            }
            $daysBeforeDue = $invoice->paid_at->diffInDays($invoice->due_date, false);
            return $daysBeforeDue <= $threshold && $daysBeforeDue < 0;
        })->count();

        return round(($earlyCount / $paidInvoices->count()) * 100, 1);
    }

    /**
     * Calculate on-time payment rate
     *
     * @param Collection $paidInvoices
     * @return float
     */
    protected function calculateOnTimePaymentRate(Collection $paidInvoices): float
    {
        $onTimeCount = $paidInvoices->filter(function ($invoice) {
            if (!$invoice->due_date || !$invoice->paid_at) {
                return false;
            }
            return $invoice->paid_at->lte($invoice->due_date);
        })->count();

        return round(($onTimeCount / $paidInvoices->count()) * 100, 1);
    }

    /**
     * Calculate late payment rate
     *
     * @param Collection $paidInvoices
     * @return float
     */
    protected function calculateLatePaymentRate(Collection $paidInvoices): float
    {
        $config = config('kinvoice.ai.insights');
        $threshold = $config['late_payment_threshold_days'];

        $lateCount = $paidInvoices->filter(function ($invoice) use ($threshold) {
            if (!$invoice->due_date || !$invoice->paid_at) {
                return false;
            }
            return $invoice->paid_at->gt($invoice->due_date->addDays($threshold));
        })->count();

        return round(($lateCount / $paidInvoices->count()) * 100, 1);
    }

    /**
     * Calculate growth trend from monthly data
     *
     * @param Collection $monthlyData
     * @return string
     */
    protected function calculateGrowthTrend(Collection $monthlyData): string
    {
        if ($monthlyData->count() < 2) {
            return 'insufficient_data';
        }

        $revenues = $monthlyData->pluck('total_revenue')->values();
        $first = $revenues->take(3)->avg();
        $last = $revenues->slice(-3)->avg();

        if ($last > $first * 1.1) {
            return 'growing';
        } elseif ($last < $first * 0.9) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    /**
     * Clear insights cache for a user
     *
     * @param User $user
     * @return void
     */
    public function clearCache(User $user): void
    {
        Cache::forget("insights:all:{$user->id}");
    }
}
