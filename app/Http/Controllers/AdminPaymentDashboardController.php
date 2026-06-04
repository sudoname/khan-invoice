<?php

namespace App\Http\Controllers;

use App\Models\PlatformTransaction;
use App\Models\PublicInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminPaymentDashboardController extends Controller
{
    /**
     * Display payment dashboard
     */
    public function index(Request $request)
    {
        // Get date range (default: last 30 days)
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->startOfDay());
        $endDate = $request->input('end_date', Carbon::now()->endOfDay());

        // Platform revenue stats
        $stats = $this->calculateStats($startDate, $endDate);

        // Get Paystack balance
        $paystackBalance = $this->getPaystackBalance();

        // Recent transactions
        $recentTransactions = PlatformTransaction::with('invoice')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Top merchants by volume
        $topMerchants = $this->getTopMerchants($startDate, $endDate);

        // Revenue trend (daily)
        $revenueTrend = $this->getRevenueTrend($startDate, $endDate);

        return view('admin.payments.dashboard', compact(
            'stats',
            'paystackBalance',
            'recentTransactions',
            'topMerchants',
            'revenueTrend',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show unsettled payments
     */
    public function unsettled()
    {
        $unsettledPayments = PlatformTransaction::unsettled()
            ->with('invoice')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $totalUnsettled = PlatformTransaction::unsettled()->sum('merchant_amount');
        $totalCount = PlatformTransaction::unsettled()->count();

        return view('admin.payments.unsettled', compact(
            'unsettledPayments',
            'totalUnsettled',
            'totalCount'
        ));
    }

    /**
     * Show all transactions
     */
    public function all(Request $request)
    {
        $query = PlatformTransaction::with('invoice');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by merchant
        if ($request->has('merchant')) {
            $query->where('merchant_name', 'like', '%' . $request->merchant . '%');
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.payments.all', compact('transactions'));
    }

    /**
     * Calculate platform statistics
     */
    private function calculateStats($startDate, $endDate): array
    {
        $baseQuery = PlatformTransaction::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [
            // Total revenue (platform commission)
            'total_revenue' => (clone $baseQuery)->sum('platform_commission'),

            // Total transactions
            'total_transactions' => (clone $baseQuery)->count(),

            // Total transaction volume
            'total_volume' => (clone $baseQuery)->sum('total_amount'),

            // Amount to merchants
            'total_to_merchants' => (clone $baseQuery)->sum('merchant_amount'),

            // Unsettled payments
            'unsettled_count' => PlatformTransaction::unsettled()->count(),
            'unsettled_amount' => PlatformTransaction::unsettled()->sum('merchant_amount'),

            // Today's revenue
            'today_revenue' => PlatformTransaction::where('status', 'success')
                ->whereDate('created_at', Carbon::today())
                ->sum('platform_commission'),

            // This month's revenue
            'month_revenue' => PlatformTransaction::where('status', 'success')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('platform_commission'),

            // Average transaction value
            'avg_transaction' => (clone $baseQuery)->avg('total_amount') ?? 0,

            // Average commission per transaction
            'avg_commission' => (clone $baseQuery)->avg('platform_commission') ?? 0,
        ];
    }

    /**
     * Get Paystack balance
     */
    private function getPaystackBalance(): array
    {
        try {
            $secretKey = config('services.paystack.secret_key');

            if (!$secretKey) {
                return [
                    'available' => 0,
                    'ledger' => 0,
                    'currency' => 'NGN',
                    'error' => 'Paystack secret key not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.paystack.co/balance');

            if ($response->successful() && $response->json('status')) {
                $balances = $response->json('data');
                $ngnBalance = collect($balances)->firstWhere('currency', 'NGN');

                return [
                    'available' => isset($ngnBalance['balance']) ? $ngnBalance['balance'] / 100 : 0,
                    'ledger' => isset($ngnBalance['balance']) ? $ngnBalance['balance'] / 100 : 0,
                    'currency' => 'NGN',
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch Paystack balance: ' . $e->getMessage());
        }

        return [
            'available' => 0,
            'ledger' => 0,
            'currency' => 'NGN',
            'error' => 'Unable to fetch balance',
        ];
    }

    /**
     * Get top merchants by transaction volume
     */
    private function getTopMerchants($startDate, $endDate)
    {
        return PlatformTransaction::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('merchant_name', 'merchant_email')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_volume')
            ->selectRaw('SUM(platform_commission) as total_commission')
            ->selectRaw('SUM(merchant_amount) as total_merchant_amount')
            ->groupBy('merchant_name', 'merchant_email')
            ->orderByDesc('total_volume')
            ->limit(10)
            ->get();
    }

    /**
     * Get revenue trend by day
     */
    private function getRevenueTrend($startDate, $endDate)
    {
        return PlatformTransaction::where('status', 'success')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'))
            ->selectRaw('SUM(platform_commission) as revenue')
            ->selectRaw('SUM(total_amount) as volume')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
