<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $query = Invoice::query();

        // If user is not admin, filter by their own data
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        // Current month stats
        $thisMonth = (clone $query)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        $lastMonth = (clone $query)->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year);

        $thisMonthRevenue = $thisMonth->sum('total_amount');
        $lastMonthRevenue = $lastMonth->sum('total_amount');
        $revenueChange = $lastMonthRevenue > 0
            ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        // Outstanding invoices (not fully paid)
        $outstandingQuery = (clone $query)->whereIn('status', ['sent', 'overdue', 'partially_paid']);
        $outstandingAmount = $outstandingQuery->sum(DB::raw('total_amount - amount_paid'));
        $outstandingCount = $outstandingQuery->count();

        // Overdue metrics
        $overdueQuery = (clone $query)->where('status', 'overdue');
        $overdueCount = $overdueQuery->count();
        $overdueAmount = $overdueQuery->sum(DB::raw('total_amount - amount_paid'));

        // This month payments received
        $thisMonthPaid = (clone $query)
            ->where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('amount_paid');

        // Collection rate
        $totalBilled = $query->sum('total_amount');
        $totalCollected = $query->sum('amount_paid');
        $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;

        return [
            Stat::make('Outstanding Amount', '₦' . number_format($outstandingAmount, 2))
                ->description($outstandingCount . ' unpaid invoices')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart([7, 12, 15, 18, 22, 19, $outstandingCount]),

            Stat::make('This Month Revenue', '₦' . number_format($thisMonthRevenue, 2))
                ->description(($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '% from last month')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),

            Stat::make('Overdue Invoices', $overdueCount)
                ->description('₦' . number_format($overdueAmount, 2) . ' overdue')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Collections This Month', '₦' . number_format($thisMonthPaid, 2))
                ->description('Payment received')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Collection Rate', number_format($collectionRate, 1) . '%')
                ->description('Overall collection efficiency')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($collectionRate >= 90 ? 'success' : ($collectionRate >= 70 ? 'warning' : 'danger')),
        ];
    }
}
