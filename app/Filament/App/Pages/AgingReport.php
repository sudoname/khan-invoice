<?php

namespace App\Filament\App\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AgingReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.app.pages.aging-report';

    protected static ?string $navigationLabel = 'Aging Report';

    protected static ?string $title = 'Accounts Receivable Aging';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    public $agingData = [];
    public $summary = [];

    public function mount(): void
    {
        $this->loadAgingData();
    }

    protected function loadAgingData(): void
    {
        $query = Invoice::query()
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->with(['customer']);

        // If user is not admin, filter by their own data
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $invoices = $query->get();

        $data = [];
        $summary = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            'over_90' => 0,
            'total' => 0,
        ];

        foreach ($invoices as $invoice) {
            $balance = $invoice->total_amount - $invoice->amount_paid;
            if ($balance <= 0) {
                continue;
            }

            $daysOverdue = now()->diffInDays($invoice->due_date, false);

            // Handle missing customer
            $customerName = $invoice->customer ? $invoice->customer->name : 'Unknown Customer';

            if (!isset($data[$customerName])) {
                $data[$customerName] = [
                    'customer' => $customerName,
                    'current' => 0,
                    '1_30' => 0,
                    '31_60' => 0,
                    '61_90' => 0,
                    'over_90' => 0,
                    'total' => 0,
                ];
            }

            // Categorize by age
            if ($daysOverdue >= 0) {
                // Not yet due
                $data[$customerName]['current'] += $balance;
                $summary['current'] += $balance;
            } elseif ($daysOverdue >= -30) {
                // 1-30 days overdue
                $data[$customerName]['1_30'] += $balance;
                $summary['1_30'] += $balance;
            } elseif ($daysOverdue >= -60) {
                // 31-60 days overdue
                $data[$customerName]['31_60'] += $balance;
                $summary['31_60'] += $balance;
            } elseif ($daysOverdue >= -90) {
                // 61-90 days overdue
                $data[$customerName]['61_90'] += $balance;
                $summary['61_90'] += $balance;
            } else {
                // Over 90 days overdue
                $data[$customerName]['over_90'] += $balance;
                $summary['over_90'] += $balance;
            }

            $data[$customerName]['total'] += $balance;
            $summary['total'] += $balance;
        }

        $this->agingData = array_values($data);
        $this->summary = $summary;
    }
}
