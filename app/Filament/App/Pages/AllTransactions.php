<?php

namespace App\Filament\App\Pages;

use App\Models\Invoice;
use App\Models\Expense;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AllTransactions extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static string $view = 'filament.app.pages.all-transactions';

    protected static ?string $navigationLabel = 'All Transactions';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    public function getTransactions(): Collection
    {
        $userId = auth()->id();
        $isAdmin = auth()->user()->isAdmin();

        // Get invoices
        $invoices = Invoice::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId))
            ->with(['customer', 'items'])
            ->get()
            ->map(function ($invoice) {
                return (object) [
                    'id' => 'INV-' . $invoice->id,
                    'date' => $invoice->issue_date,
                    'type' => 'Invoice',
                    'number' => $invoice->invoice_number,
                    'party' => $invoice->customer->name ?? 'N/A',
                    'description' => $invoice->items->pluck('description')->implode(', '),
                    'category' => 'Revenue',
                    'amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                ];
            });

        // Get expenses
        $expenses = Expense::query()
            ->when(!$isAdmin, fn ($q) => $q->where('user_id', $userId))
            ->with('vendor')
            ->get()
            ->map(function ($expense) {
                return (object) [
                    'id' => 'EXP-' . $expense->id,
                    'date' => $expense->expense_date,
                    'type' => 'Expense',
                    'number' => $expense->expense_number,
                    'party' => $expense->vendor->name ?? 'N/A',
                    'description' => $expense->description,
                    'category' => ucfirst($expense->category),
                    'amount' => $expense->total_amount,
                    'status' => $expense->status,
                ];
            });

        // Return sorted collection
        return $invoices->concat($expenses)->sortByDesc('date')->values();
    }
}
