<?php

namespace App\Filament\App\Pages;

use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalesReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.app.pages.sales-report';

    protected static ?string $navigationLabel = 'Sales Report';

    protected static ?string $title = 'Sales & Collections Report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    public $start_date;
    public $end_date;
    public $reportData = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Period')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('From Date')
                            ->required()
                            ->default(now()->startOfMonth()),
                        DatePicker::make('end_date')
                            ->label('To Date')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(2),
            ]);
    }

    public function generateReport(): void
    {
        $startDate = $this->start_date ?? now()->startOfMonth();
        $endDate = $this->end_date ?? now()->endOfMonth();

        $query = Invoice::query();

        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        // Sales metrics
        $invoices = (clone $query)->whereBetween('created_at', [$startDate, $endDate]);
        $totalBilled = $invoices->sum('total_amount');
        $invoiceCount = $invoices->count();

        // Payment metrics
        $payments = Payment::query()
            ->whereHas('invoice', function ($q) {
                if (!auth()->user()->isAdmin()) {
                    $q->where('user_id', auth()->id());
                }
            })
            ->whereBetween('payment_date', [$startDate, $endDate]);

        $totalCollected = $payments->sum('amount');
        $paymentCount = $payments->count();

        // Status breakdown
        $statusBreakdown = (clone $invoices)->select('status', DB::raw('count(*) as count, sum(total_amount) as amount'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => [
                    'count' => $item->count,
                    'amount' => $item->amount
                ]];
            })->toArray();

        // Top customers
        $topCustomers = (clone $invoices)
            ->select('customer_id', DB::raw('sum(total_amount) as total'))
            ->with('customer')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->customer->name ?? 'Unknown',
                    'total' => $item->total,
                ];
            })->toArray();

        // Payment methods breakdown
        $paymentMethods = (clone $payments)
            ->select('payment_method', DB::raw('count(*) as count, sum(amount) as amount'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [ucwords(str_replace('_', ' ', $item->payment_method)) => [
                    'count' => $item->count,
                    'amount' => $item->amount
                ]];
            })->toArray();

        $this->reportData = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'sales' => [
                'total_billed' => $totalBilled,
                'invoice_count' => $invoiceCount,
                'average_invoice' => $invoiceCount > 0 ? $totalBilled / $invoiceCount : 0,
            ],
            'collections' => [
                'total_collected' => $totalCollected,
                'payment_count' => $paymentCount,
                'average_payment' => $paymentCount > 0 ? $totalCollected / $paymentCount : 0,
            ],
            'status_breakdown' => $statusBreakdown,
            'top_customers' => $topCustomers,
            'payment_methods' => $paymentMethods,
        ];
    }

    public function refreshReport(): void
    {
        $this->generateReport();
    }
}
