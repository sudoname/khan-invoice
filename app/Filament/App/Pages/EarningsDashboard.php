<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\Payment\Payout;
use App\Models\Payment\LedgerEntry;
use App\Models\Payment\InvoicePayment;

class EarningsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Earnings Dashboard';

    protected static ?string $navigationGroup = 'Financial Reports';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.earnings-dashboard';

    protected static ?string $title = 'Earnings Dashboard';

    public array $stats = [];
    public array $recentPayouts = [];
    public array $earningsChart = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadRecentPayouts();
        $this->loadEarningsChart();
    }

    protected function loadStats(): void
    {
        $userId = auth()->id();

        // Total Payments Received
        $totalPayments = InvoicePayment::whereHas('invoice', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('reconciliation_status', 'RECONCILED')
        ->sum('amount_paid');

        // Total Fees Paid
        $totalFees = InvoicePayment::whereHas('invoice', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('reconciliation_status', 'RECONCILED')
        ->sum('fees_paid');

        // Net Received
        $netReceived = InvoicePayment::whereHas('invoice', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('reconciliation_status', 'RECONCILED')
        ->sum('net_received');

        // Total Payouts Completed
        $totalPayouts = Payout::where('user_id', $userId)
            ->where('status', 'COMPLETED')
            ->sum('net_amount');

        // Total Payout Fees
        $payoutFees = Payout::where('user_id', $userId)
            ->where('status', 'COMPLETED')
            ->sum('payout_fee');

        // Available Balance (from ledger)
        $latestEntry = LedgerEntry::where('user_id', $userId)
            ->latest('created_at')
            ->first();
        $availableBalance = $latestEntry ? $latestEntry->balance_after : 0;

        // Pending Payouts
        $pendingPayouts = Payout::where('user_id', $userId)
            ->whereIn('status', ['PENDING', 'PROCESSING'])
            ->sum('gross_amount');

        $this->stats = [
            'total_payments' => $totalPayments,
            'total_fees' => $totalFees,
            'net_received' => $netReceived,
            'total_payouts' => $totalPayouts,
            'payout_fees' => $payoutFees,
            'available_balance' => $availableBalance,
            'pending_payouts' => $pendingPayouts,
        ];
    }

    protected function loadRecentPayouts(): void
    {
        $this->recentPayouts = Payout::where('user_id', auth()->id())
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($payout) => [
                'reference' => $payout->reference,
                'net_amount' => $payout->net_amount,
                'status' => $payout->status,
                'payout_type' => $payout->payout_type,
                'created_at' => $payout->created_at->format('M d, Y'),
            ])
            ->toArray();
    }

    protected function loadEarningsChart(): void
    {
        $userId = auth()->id();

        // Get earnings for last 12 months
        $earnings = InvoicePayment::whereHas('invoice', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('reconciliation_status', 'RECONCILED')
        ->where('paid_at', '>=', now()->subMonths(12))
        ->select(
            DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
            DB::raw('SUM(amount_paid) as total_amount'),
            DB::raw('SUM(fees_paid) as total_fees'),
            DB::raw('SUM(net_received) as net_amount')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $this->earningsChart = $earnings->map(fn ($item) => [
            'month' => date('M Y', strtotime($item->month . '-01')),
            'amount' => $item->total_amount,
            'fees' => $item->total_fees,
            'net' => $item->net_amount,
        ])->toArray();
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 3;
    }
}
