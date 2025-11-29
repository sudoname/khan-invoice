<?php

namespace App\Filament\Admin\Resources\UserResource\Widgets;

use App\Models\Invoice;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Total users
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $activeUsers = User::whereHas('invoices')->count();

        // All invoices across all users
        $allInvoices = Invoice::query();

        // Total invoiced amount
        $totalInvoiced = $allInvoices->sum('total_amount');

        // Total paid
        $totalPaid = Invoice::where('status', 'paid')->sum('total_amount');

        // Total amount paid (including partially paid)
        $totalAmountPaid = Invoice::sum('amount_paid');

        // Pending/Outstanding
        $totalPending = Invoice::whereIn('status', ['draft', 'sent', 'overdue', 'partially_paid'])
            ->sum('total_amount') - Invoice::whereIn('status', ['partially_paid'])
            ->sum('amount_paid');

        // Overdue
        $totalOverdue = Invoice::where('status', 'overdue')->sum('total_amount');

        // Total invoices by status
        $totalProcessed = Invoice::whereIn('status', ['paid', 'partially_paid', 'sent', 'overdue'])->count();
        $totalDraft = Invoice::where('status', 'draft')->count();
        $paidCount = Invoice::where('status', 'paid')->count();

        // Calculate percentages
        $verificationRate = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0;
        $activeRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        $collectionRate = $totalInvoiced > 0 ? round(($totalAmountPaid / $totalInvoiced) * 100, 1) : 0;

        // This month's stats
        $thisMonthInvoiced = Invoice::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $lastMonthInvoiced = Invoice::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total_amount');

        $monthlyGrowth = $lastMonthInvoiced > 0
            ? round((($thisMonthInvoiced - $lastMonthInvoiced) / $lastMonthInvoiced) * 100, 1)
            : 0;

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description("{$verifiedUsers} verified ({$verificationRate}%)")
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 12, 15, 18, 22, 25, $totalUsers]),

            Stat::make('Active Users', number_format($activeUsers))
                ->description("{$activeRate}% of all users")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([3, 7, 9, 12, 15, 18, $activeUsers]),

            Stat::make('Total Invoiced', '₦' . number_format($totalInvoiced, 2))
                ->description("Across all users")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([
                    $totalInvoiced * 0.3,
                    $totalInvoiced * 0.5,
                    $totalInvoiced * 0.7,
                    $totalInvoiced * 0.85,
                    $totalInvoiced * 0.92,
                    $totalInvoiced
                ]),

            Stat::make('Total Paid', '₦' . number_format($totalAmountPaid, 2))
                ->description("{$paidCount} invoices • {$collectionRate}% collection rate")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([
                    $totalAmountPaid * 0.4,
                    $totalAmountPaid * 0.6,
                    $totalAmountPaid * 0.75,
                    $totalAmountPaid * 0.88,
                    $totalAmountPaid * 0.95,
                    $totalAmountPaid
                ]),

            Stat::make('Outstanding', '₦' . number_format($totalPending, 2))
                ->description($totalOverdue > 0 ? '₦' . number_format($totalOverdue, 2) . ' overdue' : 'No overdue invoices')
                ->descriptionIcon($totalOverdue > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
                ->color($totalOverdue > 0 ? 'warning' : 'gray')
                ->chart([
                    $totalPending * 0.6,
                    $totalPending * 0.8,
                    $totalPending * 0.9,
                    $totalPending * 0.95,
                    $totalPending,
                    $totalPending * 0.85
                ]),

            Stat::make('Invoices Processed', number_format($totalProcessed))
                ->description("{$totalDraft} drafts pending")
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary')
                ->chart([
                    $totalProcessed * 0.5,
                    $totalProcessed * 0.65,
                    $totalProcessed * 0.78,
                    $totalProcessed * 0.87,
                    $totalProcessed * 0.94,
                    $totalProcessed
                ]),

            Stat::make('This Month', '₦' . number_format($thisMonthInvoiced, 2))
                ->description($monthlyGrowth >= 0 ? "+{$monthlyGrowth}% from last month" : "{$monthlyGrowth}% from last month")
                ->descriptionIcon($monthlyGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyGrowth >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastMonthInvoiced * 0.7,
                    $lastMonthInvoiced * 0.85,
                    $lastMonthInvoiced,
                    $thisMonthInvoiced * 0.6,
                    $thisMonthInvoiced * 0.8,
                    $thisMonthInvoiced
                ]),

            Stat::make('Avg Invoice Value', $totalProcessed > 0 ? '₦' . number_format($totalInvoiced / $totalProcessed, 2) : '₦0.00')
                ->description("Based on {$totalProcessed} invoices")
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
