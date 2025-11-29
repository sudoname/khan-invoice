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
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Current totals
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $activeUsers = User::whereHas('invoices')->count();

        // Last month totals for comparison
        $lastMonthUsers = User::where('created_at', '<=', $lastMonthEnd)->count();
        $lastMonthActiveUsers = User::where('created_at', '<=', $lastMonthEnd)
            ->whereHas('invoices', function ($query) use ($lastMonthEnd) {
                $query->where('created_at', '<=', $lastMonthEnd);
            })->count();

        // Total invoiced amount
        $totalInvoiced = Invoice::sum('total_amount');
        $lastMonthTotalInvoiced = Invoice::where('created_at', '<=', $lastMonthEnd)->sum('total_amount');

        // Total amount paid (including partially paid)
        $totalAmountPaid = Invoice::sum('amount_paid');
        $lastMonthAmountPaid = Invoice::where('created_at', '<=', $lastMonthEnd)->sum('amount_paid');

        // Pending/Outstanding
        $totalPending = Invoice::whereIn('status', ['draft', 'sent', 'overdue', 'partially_paid'])
            ->sum('total_amount') - Invoice::whereIn('status', ['partially_paid'])
            ->sum('amount_paid');

        $lastMonthPending = Invoice::where('created_at', '<=', $lastMonthEnd)
            ->whereIn('status', ['draft', 'sent', 'overdue', 'partially_paid'])
            ->sum('total_amount') - Invoice::where('created_at', '<=', $lastMonthEnd)
            ->whereIn('status', ['partially_paid'])
            ->sum('amount_paid');

        // Overdue
        $totalOverdue = Invoice::where('status', 'overdue')->sum('total_amount');

        // Total invoices by status
        $totalProcessed = Invoice::whereIn('status', ['paid', 'partially_paid', 'sent', 'overdue'])->count();
        $lastMonthProcessed = Invoice::where('created_at', '<=', $lastMonthEnd)
            ->whereIn('status', ['paid', 'partially_paid', 'sent', 'overdue'])->count();

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

        // Calculate growth rates
        $usersGrowth = $lastMonthUsers > 0
            ? round((($totalUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1)
            : ($totalUsers > 0 ? 100 : 0);

        $activeUsersGrowth = $lastMonthActiveUsers > 0
            ? round((($activeUsers - $lastMonthActiveUsers) / $lastMonthActiveUsers) * 100, 1)
            : ($activeUsers > 0 ? 100 : 0);

        $invoicedGrowth = $lastMonthTotalInvoiced > 0
            ? round((($totalInvoiced - $lastMonthTotalInvoiced) / $lastMonthTotalInvoiced) * 100, 1)
            : ($totalInvoiced > 0 ? 100 : 0);

        $paidGrowth = $lastMonthAmountPaid > 0
            ? round((($totalAmountPaid - $lastMonthAmountPaid) / $lastMonthAmountPaid) * 100, 1)
            : ($totalAmountPaid > 0 ? 100 : 0);

        $pendingGrowth = $lastMonthPending > 0
            ? round((($totalPending - $lastMonthPending) / $lastMonthPending) * 100, 1)
            : ($totalPending > 0 ? 100 : 0);

        $processedGrowth = $lastMonthProcessed > 0
            ? round((($totalProcessed - $lastMonthProcessed) / $lastMonthProcessed) * 100, 1)
            : ($totalProcessed > 0 ? 100 : 0);

        $avgInvoiceValue = $totalProcessed > 0 ? $totalInvoiced / $totalProcessed : 0;
        $lastMonthAvgValue = $lastMonthProcessed > 0 ? $lastMonthTotalInvoiced / $lastMonthProcessed : 0;
        $avgValueGrowth = $lastMonthAvgValue > 0
            ? round((($avgInvoiceValue - $lastMonthAvgValue) / $lastMonthAvgValue) * 100, 1)
            : ($avgInvoiceValue > 0 ? 100 : 0);

        $monthlyGrowth = $lastMonthInvoiced > 0
            ? round((($thisMonthInvoiced - $lastMonthInvoiced) / $lastMonthInvoiced) * 100, 1)
            : ($thisMonthInvoiced > 0 ? 100 : 0);

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description(($usersGrowth >= 0 ? '+' : '') . $usersGrowth . '% from last month')
                ->descriptionIcon($usersGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($usersGrowth >= 0 ? 'success' : 'danger')
                ->chart([7, 12, 15, 18, 22, 25, $totalUsers]),

            Stat::make('Active Users', number_format($activeUsers))
                ->description(($activeUsersGrowth >= 0 ? '+' : '') . $activeUsersGrowth . '% from last month')
                ->descriptionIcon($activeUsersGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($activeUsersGrowth >= 0 ? 'success' : 'danger')
                ->chart([3, 7, 9, 12, 15, 18, $activeUsers]),

            Stat::make('Total Invoiced', '₦' . number_format($totalInvoiced, 2))
                ->description(($invoicedGrowth >= 0 ? '+' : '') . $invoicedGrowth . '% from last month')
                ->descriptionIcon($invoicedGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($invoicedGrowth >= 0 ? 'success' : 'danger')
                ->chart([
                    $totalInvoiced * 0.3,
                    $totalInvoiced * 0.5,
                    $totalInvoiced * 0.7,
                    $totalInvoiced * 0.85,
                    $totalInvoiced * 0.92,
                    $totalInvoiced
                ]),

            Stat::make('Total Paid', '₦' . number_format($totalAmountPaid, 2))
                ->description(($paidGrowth >= 0 ? '+' : '') . $paidGrowth . '% from last month')
                ->descriptionIcon($paidGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($paidGrowth >= 0 ? 'success' : 'danger')
                ->chart([
                    $totalAmountPaid * 0.4,
                    $totalAmountPaid * 0.6,
                    $totalAmountPaid * 0.75,
                    $totalAmountPaid * 0.88,
                    $totalAmountPaid * 0.95,
                    $totalAmountPaid
                ]),

            Stat::make('Outstanding', '₦' . number_format($totalPending, 2))
                ->description(($pendingGrowth >= 0 ? '+' : '') . $pendingGrowth . '% from last month')
                ->descriptionIcon($pendingGrowth >= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($pendingGrowth >= 0 ? 'danger' : 'success')
                ->chart([
                    $totalPending * 0.6,
                    $totalPending * 0.8,
                    $totalPending * 0.9,
                    $totalPending * 0.95,
                    $totalPending,
                    $totalPending * 0.85
                ]),

            Stat::make('Invoices Processed', number_format($totalProcessed))
                ->description(($processedGrowth >= 0 ? '+' : '') . $processedGrowth . '% from last month')
                ->descriptionIcon($processedGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($processedGrowth >= 0 ? 'success' : 'danger')
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

            Stat::make('Avg Invoice Value', $avgInvoiceValue > 0 ? '₦' . number_format($avgInvoiceValue, 2) : '₦0.00')
                ->description(($avgValueGrowth >= 0 ? '+' : '') . $avgValueGrowth . '% from last month')
                ->descriptionIcon($avgValueGrowth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($avgValueGrowth >= 0 ? 'success' : 'danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
