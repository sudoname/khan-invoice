<?php

namespace App\Filament\App\Pages;

use App\Models\Invoice;
use App\Models\Income;
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

    protected static ?string $navigationGroup = 'Insights';

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

        // Revenue from paid invoices
        $invoiceRevenueQuery = Invoice::query()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'paid');

        if (!$isAdmin) {
            $invoiceRevenueQuery->where('user_id', $userId);
        }

        $invoiceRevenue = $invoiceRevenueQuery->sum('total_amount');
        $invoiceRevenueByMonth = $invoiceRevenueQuery
            ->selectRaw("MONTH(issue_date) as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Revenue from direct income (not tied to invoices)
        // Exclude auto-created income from invoices to prevent double-counting
        $directIncomeQuery = Income::query()
            ->whereBetween('income_date', [$startDate, $endDate])
            ->whereNull('invoice_id'); // Only manual income entries

        if (!$isAdmin) {
            $directIncomeQuery->where('user_id', $userId);
        }

        $directIncome = $directIncomeQuery->sum('total_amount');
        $directIncomeByMonth = $directIncomeQuery
            ->selectRaw("MONTH(income_date) as month, SUM(total_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $directIncomeByCategory = $directIncomeQuery
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Total revenue (invoices + direct income)
        $totalRevenue = $invoiceRevenue + $directIncome;

        // Combined revenue by month
        $revenueByMonth = [];
        for ($month = 1; $month <= 12; $month++) {
            $revenueByMonth[$month] =
                ($invoiceRevenueByMonth[$month] ?? 0) +
                ($directIncomeByMonth[$month] ?? 0);
        }

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
            ->selectRaw("MONTH(expense_date) as month, SUM(total_amount) as total")
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
                'invoice_revenue' => $invoiceRevenue,
                'direct_income' => $directIncome,
                'direct_income_by_category' => $directIncomeByCategory,
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
