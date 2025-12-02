<?php

namespace App\Filament\Admin\Resources\PublicInvoiceResource\Widgets;

use App\Models\PublicInvoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PublicInvoiceStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Get all public invoices
        $allInvoices = PublicInvoice::query();

        // Count by status
        $totalCount = $allInvoices->count();
        $sentCount = PublicInvoice::where('payment_status', 'sent')->count();
        $paidCount = PublicInvoice::where('payment_status', 'paid')->count();
        $partiallyPaidCount = PublicInvoice::where('payment_status', 'partially_paid')->count();
        $overdueCount = PublicInvoice::where('payment_status', 'overdue')->count();
        $pendingCount = PublicInvoice::where('payment_status', 'pending')->count();

        // Calculate totals
        $totalRevenue = PublicInvoice::sum('total_amount');
        $paidAmount = PublicInvoice::where('payment_status', 'paid')->sum('amount_paid');
        $unpaidAmount = $totalRevenue - $paidAmount;

        // Calculate conversion rate (paid vs total)
        $conversionRate = $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0;

        return [
            Stat::make('Total Public Invoices', number_format($totalCount))
                ->description('All invoices created')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([7, 12, 8, 15, 10, 14, $totalCount]),

            Stat::make('Total Revenue', '₦' . number_format($totalRevenue, 2))
                ->description('Sum of all invoice amounts')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([1000, 2500, 3200, 4100, $totalRevenue]),

            Stat::make('Paid Invoices', number_format($paidCount))
                ->description('₦' . number_format($paidAmount, 2) . ' collected')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Unpaid Amount', '₦' . number_format($unpaidAmount, 2))
                ->description(number_format($totalCount - $paidCount) . ' invoices pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Conversion Rate', $conversionRate . '%')
                ->description('Paid / Total invoices')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($conversionRate >= 50 ? 'success' : 'warning'),

            Stat::make('Status Breakdown', '')
                ->description("Sent: {$sentCount} | Paid: {$paidCount} | Pending: {$pendingCount} | Partial: {$partiallyPaidCount} | Overdue: {$overdueCount}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
