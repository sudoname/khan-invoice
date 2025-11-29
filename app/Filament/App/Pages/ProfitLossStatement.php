<?php

namespace App\Filament\App\Pages;

use App\Models\Invoice;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ProfitLossStatement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.app.pages.profit-loss-statement';

    protected static ?string $navigationLabel = 'Profit & Loss';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];
    public ?array $reportData = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfYear(),
            'end_date' => now(),
        ]);

        $this->refreshReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Start Date')
                    ->required()
                    ->default(now()->startOfYear()),
                Forms\Components\DatePicker::make('end_date')
                    ->label('End Date')
                    ->required()
                    ->default(now()),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function refreshReport(): void
    {
        $data = $this->form->getState();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $userId = auth()->id();
        $isAdmin = auth()->user()->isAdmin();

        // Revenue (from paid invoices)
        $revenueQuery = Invoice::query()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'paid');

        if (!$isAdmin) {
            $revenueQuery->where('user_id', $userId);
        }

        $totalRevenue = $revenueQuery->sum('total_amount');
        $revenueByMonth = $revenueQuery
            ->selectRaw("CAST(strftime('%m', issue_date) AS INTEGER) as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Expenses
        $expensesQuery = Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'paid');

        if (!$isAdmin) {
            $expensesQuery->where('user_id', $userId);
        }

        $totalExpenses = $expensesQuery->sum('total_amount');
        $expensesByCategory = $expensesQuery
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $expensesByMonth = $expensesQuery
            ->selectRaw("CAST(strftime('%m', expense_date) AS INTEGER) as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Net Income
        $netIncome = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0;

        $this->reportData = [
            'revenue' => [
                'total' => $totalRevenue,
                'by_month' => $revenueByMonth,
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'by_category' => $expensesByCategory,
                'by_month' => $expensesByMonth,
            ],
            'net_income' => $netIncome,
            'profit_margin' => $profitMargin,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
