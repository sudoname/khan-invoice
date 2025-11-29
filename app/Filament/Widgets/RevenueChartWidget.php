<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend (Last 6 Months)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $query = Invoice::query();

        // If user is not admin, filter by their own data
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $months = [];
        $revenue = [];
        $collected = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            $monthQuery = (clone $query)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);

            $months[] = $monthName;
            $revenue[] = $monthQuery->sum('total_amount');
            $collected[] = $monthQuery->sum('amount_paid');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Billed',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Amount Collected',
                    'data' => $collected,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
