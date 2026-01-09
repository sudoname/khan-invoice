<?php

namespace App\Services\AI;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuggestionService
{
    /**
     * Suggest customers based on query and user history
     *
     * @param User $user
     * @param string $query
     * @return Collection
     */
    public function suggestCustomers(User $user, string $query = ''): Collection
    {
        $cacheKey = "suggestions:customers:{$user->id}:" . md5($query);
        $config = config('kinvoice.ai.suggestions');

        return Cache::remember($cacheKey, $config['cache_ttl'], function () use ($user, $query, $config) {
            // Query customer database with search
            $customersQuery = Customer::where('user_id', $user->id)
                ->when($query, function ($q) use ($query) {
                    $q->where(function ($subQuery) use ($query) {
                        $subQuery->where('name', 'LIKE', "%{$query}%")
                            ->orWhere('company_name', 'LIKE', "%{$query}%")
                            ->orWhere('email', 'LIKE', "%{$query}%");
                    });
                });

            // Get customer usage statistics
            $lookbackDate = now()->subDays($config['lookback_days']);

            $customers = $customersQuery->get()->map(function ($customer) use ($user, $lookbackDate, $config) {
                // Count recent invoices
                $recentInvoiceCount = Invoice::where('user_id', $user->id)
                    ->where('customer_id', $customer->id)
                    ->where('created_at', '>=', $lookbackDate)
                    ->count();

                // Get last used date
                $lastUsed = Invoice::where('user_id', $user->id)
                    ->where('customer_id', $customer->id)
                    ->max('created_at');

                // Calculate recency score (days since last use, normalized)
                $daysSinceLastUse = $lastUsed ? now()->diffInDays($lastUsed) : 999;
                $recencyScore = max(0, 1 - ($daysSinceLastUse / $config['lookback_days']));

                // Calculate frequency score (normalized by max count)
                $frequencyScore = $recentInvoiceCount;

                // Weighted composite score
                $score = ($recencyScore * $config['recency_weight']) +
                         ($frequencyScore * $config['frequency_weight']);

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'recent_invoice_count' => $recentInvoiceCount,
                    'last_used' => $lastUsed?->toDateTimeString(),
                    'score' => round($score, 4),
                ];
            });

            // Sort by score descending and limit results
            return $customers->sortByDesc('score')
                ->take($config['max_results'])
                ->values();
        });
    }

    /**
     * Suggest line items based on query and user history
     *
     * @param User $user
     * @param string $query
     * @param int|null $customerId
     * @return Collection
     */
    public function suggestItems(User $user, string $query = '', ?int $customerId = null): Collection
    {
        $cacheKey = "suggestions:items:{$user->id}:" . md5($query . $customerId);
        $config = config('kinvoice.ai.suggestions');

        return Cache::remember($cacheKey, $config['cache_ttl'], function () use ($user, $query, $customerId, $config) {
            $lookbackDate = now()->subDays($config['lookback_days']);

            // Get invoice items from user's invoices
            $itemsQuery = InvoiceItem::select([
                    'invoice_items.description',
                    DB::raw('AVG(invoice_items.unit_price) as avg_price'),
                    DB::raw('AVG(invoice_items.quantity) as avg_quantity'),
                    DB::raw('COUNT(*) as usage_count'),
                    DB::raw('MAX(invoices.created_at) as last_used'),
                ])
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.user_id', $user->id)
                ->where('invoices.created_at', '>=', $lookbackDate)
                ->when($query, function ($q) use ($query) {
                    $q->where('invoice_items.description', 'LIKE', "%{$query}%");
                })
                ->when($customerId, function ($q) use ($customerId) {
                    $q->where('invoices.customer_id', $customerId);
                })
                ->groupBy('invoice_items.description')
                ->orderByDesc('usage_count')
                ->orderByDesc('last_used')
                ->limit($config['max_results'])
                ->get();

            return $itemsQuery->map(function ($item) use ($config) {
                // Calculate recency score
                $daysSinceLastUse = $item->last_used ? now()->diffInDays($item->last_used) : 999;
                $recencyScore = max(0, 1 - ($daysSinceLastUse / $config['lookback_days']));

                // Frequency score
                $frequencyScore = $item->usage_count;

                // Composite score
                $score = ($recencyScore * $config['recency_weight']) +
                         ($frequencyScore * $config['frequency_weight']);

                return [
                    'description' => $item->description,
                    'suggested_price' => round($item->avg_price, 2),
                    'suggested_quantity' => round($item->avg_quantity, 2),
                    'usage_count' => $item->usage_count,
                    'last_used' => $item->last_used,
                    'score' => round($score, 4),
                ];
            })->sortByDesc('score')->values();
        });
    }

    /**
     * Suggest due date based on user/customer payment history
     *
     * @param User $user
     * @param int|null $customerId
     * @return array
     */
    public function suggestDueDate(User $user, ?int $customerId = null): array
    {
        $cacheKey = "suggestions:due_date:{$user->id}:{$customerId}";
        $config = config('kinvoice.ai.suggestions');

        return Cache::remember($cacheKey, $config['cache_ttl'], function () use ($user, $customerId) {
            $lookbackDate = now()->subDays(config('kinvoice.ai.suggestions.lookback_days'));

            // Get paid invoices to analyze payment terms
            $paidInvoices = Invoice::where('user_id', $user->id)
                ->where('status', 'paid')
                ->where('created_at', '>=', $lookbackDate)
                ->when($customerId, function ($q) use ($customerId) {
                    $q->where('customer_id', $customerId);
                })
                ->whereNotNull('issue_date')
                ->whereNotNull('paid_at')
                ->get();

            if ($paidInvoices->isEmpty()) {
                // No history, suggest default 30 days
                return [
                    'suggested_days' => 30,
                    'suggested_date' => now()->addDays(30)->format('Y-m-d'),
                    'confidence' => 'low',
                    'reason' => 'Default payment terms (no history)',
                ];
            }

            // Calculate actual payment durations
            $paymentDurations = $paidInvoices->map(function ($invoice) {
                return $invoice->issue_date->diffInDays($invoice->paid_at);
            });

            // Calculate median payment duration
            $sorted = $paymentDurations->sort()->values();
            $count = $sorted->count();
            $median = $count % 2 === 0
                ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
                : $sorted[floor($count / 2)];

            // Round to nearest 7 days for cleaner terms
            $suggestedDays = max(7, round($median / 7) * 7);

            return [
                'suggested_days' => (int) $suggestedDays,
                'suggested_date' => now()->addDays($suggestedDays)->format('Y-m-d'),
                'confidence' => $count >= 5 ? 'high' : ($count >= 3 ? 'medium' : 'low'),
                'reason' => $customerId
                    ? "Based on {$count} paid invoices from this customer (median: " . round($median) . " days)"
                    : "Based on {$count} paid invoices (median: " . round($median) . " days)",
                'sample_size' => $count,
                'median_payment_days' => round($median, 1),
                'min_payment_days' => $paymentDurations->min(),
                'max_payment_days' => $paymentDurations->max(),
            ];
        });
    }

    /**
     * Clear suggestion cache for a user
     *
     * @param User $user
     * @return void
     */
    public function clearCache(User $user): void
    {
        $patterns = [
            "suggestions:customers:{$user->id}:*",
            "suggestions:items:{$user->id}:*",
            "suggestions:due_date:{$user->id}:*",
        ];

        foreach ($patterns as $pattern) {
            // Note: This is a simplified version. In production, you'd want a more robust cache clearing mechanism
            Cache::forget($pattern);
        }

        Log::info('Cleared suggestion cache', ['user_id' => $user->id]);
    }

    /**
     * Get suggestion statistics for a user
     *
     * @param User $user
     * @return array
     */
    public function getStatistics(User $user): array
    {
        $lookbackDate = now()->subDays(config('kinvoice.ai.suggestions.lookback_days'));

        return [
            'customers' => [
                'total' => Customer::where('user_id', $user->id)->count(),
                'active' => Invoice::where('user_id', $user->id)
                    ->where('created_at', '>=', $lookbackDate)
                    ->distinct('customer_id')
                    ->count('customer_id'),
            ],
            'items' => [
                'unique_descriptions' => InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.user_id', $user->id)
                    ->where('invoices.created_at', '>=', $lookbackDate)
                    ->distinct('description')
                    ->count('description'),
                'total_line_items' => InvoiceItem::join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.user_id', $user->id)
                    ->where('invoices.created_at', '>=', $lookbackDate)
                    ->count(),
            ],
            'invoices' => [
                'total' => Invoice::where('user_id', $user->id)->count(),
                'recent' => Invoice::where('user_id', $user->id)
                    ->where('created_at', '>=', $lookbackDate)
                    ->count(),
                'paid' => Invoice::where('user_id', $user->id)
                    ->where('status', 'paid')
                    ->where('created_at', '>=', $lookbackDate)
                    ->count(),
            ],
        ];
    }
}
