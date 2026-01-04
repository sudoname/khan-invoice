<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;

class InvoiceObserver
{
    /**
     * Handle the Invoice "creating" event.
     * Set default values for totals before first save
     */
    public function creating(Invoice $invoice): void
    {
        // Set default values for totals if not already set
        // These will be recalculated when items are added via InvoiceItemObserver
        if (!isset($invoice->sub_total)) {
            $invoice->sub_total = 0;
        }
        if (!isset($invoice->vat_amount)) {
            $invoice->vat_amount = 0;
        }
        if (!isset($invoice->wht_amount)) {
            $invoice->wht_amount = 0;
        }
        if (!isset($invoice->total_amount)) {
            $invoice->total_amount = 0;
        }
    }

    /**
     * Handle the Invoice "created" event.
     * Auto-create expense for recipient if they're a registered user
     */
    public function created(Invoice $invoice): void
    {
        $this->createExpenseForRecipient($invoice);
    }

    /**
     * Handle the Invoice "updating" event.
     * This fires BEFORE the model is saved to check if tax/discount fields changed
     */
    public function updating(Invoice $invoice): void
    {
        // Check if VAT rate, WHT rate, or discount total changed
        if ($invoice->isDirty(['vat_rate', 'wht_rate', 'discount_total'])) {
            $this->recalculateTotals($invoice);
        }
    }

    /**
     * Handle the Invoice "updated" event.
     * Sync expense status when invoice status changes
     */
    public function updated(Invoice $invoice): void
    {
        // If invoice status changed, update linked expense and create income if paid
        if ($invoice->isDirty('status')) {
            $this->syncExpenseStatus($invoice);

            // Create income record when invoice becomes paid
            if ($invoice->status === 'paid') {
                $this->createIncomeForPaidInvoice($invoice);
            }
        }
    }

    /**
     * Create expense record for invoice recipient if they're a registered user
     */
    protected function createExpenseForRecipient(Invoice $invoice): void
    {
        // Skip if customer doesn't exist or has no email
        if (!$invoice->customer || !$invoice->customer->email) {
            return;
        }

        // Find user by customer email
        $recipientUser = User::where('email', $invoice->customer->email)->first();

        // If recipient is not a registered user, skip
        if (!$recipientUser) {
            return;
        }

        // Don't create expense for yourself (if you invoice yourself)
        if ($recipientUser->id === $invoice->user_id) {
            return;
        }

        // Map invoice status to expense status
        $expenseStatus = match($invoice->status) {
            'paid' => 'paid',
            'cancelled' => 'cancelled',
            default => 'pending'
        };

        // Create expense for the recipient
        Expense::create([
            'user_id' => $recipientUser->id,
            'received_invoice_id' => $invoice->id,
            'business_profile_id' => null, // Can be set later by user
            'vendor_id' => null, // Can link to vendor later
            'expense_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
            'category' => 'services', // Default category for received invoices
            'description' => "Invoice #{$invoice->invoice_number} from " . ($invoice->businessProfile->business_name ?? $invoice->user->name),
            'reference_number' => $invoice->invoice_number,
            'payment_method' => null, // To be filled when paid
            'status' => $expenseStatus,
            'currency' => $invoice->currency,
            'amount' => $invoice->sub_total ?? $invoice->total_amount,
            'tax_amount' => $invoice->vat_amount ?? 0,
            'total_amount' => $invoice->total_amount,
            'notes' => "Automatically created from received invoice",
        ]);
    }

    /**
     * Sync expense status when invoice status changes
     */
    protected function syncExpenseStatus(Invoice $invoice): void
    {
        // Find linked expense
        $expense = Expense::where('received_invoice_id', $invoice->id)->first();

        if (!$expense) {
            return;
        }

        // Map invoice status to expense status
        $newStatus = match($invoice->status) {
            'paid' => 'paid',
            'cancelled' => 'cancelled',
            'overdue' => 'overdue',
            default => 'pending'
        };

        // Update expense status
        $expense->update([
            'status' => $newStatus,
        ]);
    }

    /**
     * Create income record when invoice is paid
     */
    protected function createIncomeForPaidInvoice(Invoice $invoice): void
    {
        // Check if income already exists for this invoice
        $existingIncome = Income::where('invoice_id', $invoice->id)->first();

        if ($existingIncome) {
            return; // Income already created
        }

        // Determine payment method from the most recent payment record
        $paymentMethod = 'other';
        $lastPayment = $invoice->payments()->latest()->first();
        if ($lastPayment) {
            $paymentMethod = match($lastPayment->payment_method) {
                'paystack' => 'card',
                'bank_transfer' => 'bank_transfer',
                'cash' => 'cash',
                default => 'other'
            };
        }

        // Create income record
        Income::create([
            'user_id' => $invoice->user_id,
            'business_profile_id' => $invoice->business_profile_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'income_number' => Income::generateIncomeNumber(),
            'income_date' => $invoice->paid_at ?? now(),
            'category' => 'service_revenue', // Default category for invoice-based income
            'description' => "Payment received for Invoice #{$invoice->invoice_number}" .
                           ($invoice->customer ? " from {$invoice->customer->name}" : ""),
            'payment_method' => $paymentMethod,
            'reference_number' => $invoice->invoice_number,
            'currency' => $invoice->currency,
            'amount' => $invoice->sub_total ?? ($invoice->total_amount - ($invoice->vat_amount ?? 0)),
            'tax_amount' => $invoice->vat_amount ?? 0,
            'total_amount' => $invoice->amount_paid, // Use actual amount paid
            'notes' => "Automatically created from paid invoice",
        ]);
    }

    /**
     * Recalculate invoice totals based on current rates
     */
    protected function recalculateTotals(Invoice $invoice): void
    {
        // Calculate subtotal from all items
        $subTotal = $invoice->items()->sum('line_total');

        // Apply invoice-level discount
        $discountTotal = $invoice->discount_total ?? 0;
        $afterDiscount = $subTotal - $discountTotal;

        // Calculate VAT
        $vatRate = $invoice->vat_rate ?? 0;
        $vatAmount = $afterDiscount * ($vatRate / 100);

        // Calculate WHT (deducted from total)
        $whtRate = $invoice->wht_rate ?? 0;
        $whtAmount = $afterDiscount * ($whtRate / 100);

        // Calculate final total
        $totalAmount = $afterDiscount + $vatAmount - $whtAmount;

        // Update invoice totals
        $invoice->sub_total = $subTotal;
        $invoice->vat_amount = $vatAmount;
        $invoice->wht_amount = $whtAmount;
        $invoice->total_amount = $totalAmount;
    }
}
