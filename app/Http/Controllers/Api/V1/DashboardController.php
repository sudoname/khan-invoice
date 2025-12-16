<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        // Invoice statistics - admin sees all, regular users see only their own
        $invoices = Invoice::query();
        if (!$isAdmin) {
            $invoices->where('user_id', $user->id);
        }
        $totalInvoices = $invoices->count();
        $paidInvoices = (clone $invoices)->where('payment_status', 'paid')->count();
        $pendingInvoices = (clone $invoices)->whereIn('payment_status', ['pending', 'sent'])->count();
        $overdueInvoices = (clone $invoices)->where('payment_status', 'overdue')->count();

        // Financial statistics
        $totalAmount = (clone $invoices)->sum('total_amount');
        $paidAmount = (clone $invoices)->where('payment_status', 'paid')->sum('total_amount');
        $pendingAmount = (clone $invoices)->whereIn('payment_status', ['pending', 'sent', 'overdue'])->sum('total_amount');

        // Customer statistics - admin sees all, regular users see only their own
        $totalCustomers = Customer::query();
        if (!$isAdmin) {
            $totalCustomers->where('user_id', $user->id);
        }
        $totalCustomers = $totalCustomers->count();

        // Recent invoices
        $recentInvoicesQuery = Invoice::query();
        if (!$isAdmin) {
            $recentInvoicesQuery->where('user_id', $user->id);
        }
        $recentInvoices = $recentInvoicesQuery
            ->with(['customer'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer?->name,
                    'total_amount' => (float) $invoice->total_amount,
                    'formatted_total' => $invoice->currency . ' ' . number_format($invoice->total_amount, 2),
                    'payment_status' => $invoice->payment_status,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'created_at' => $invoice->created_at->format('Y-m-d'),
                ];
            });

        // Recent payments - admin sees all, regular users see only their own
        $recentPaymentsQuery = PaymentTransaction::query();
        if (!$isAdmin) {
            $recentPaymentsQuery->where('user_id', $user->id);
        }
        $recentPayments = $recentPaymentsQuery
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => $payment->formatted_type,
                    'amount' => (float) $payment->amount,
                    'formatted_amount' => $payment->formatted_amount,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // Monthly revenue (last 6 months)
        // Use appropriate date format function based on database driver
        $dateFormat = DB::getDriverName() === 'sqlite'
            ? 'strftime("%Y-%m", paid_at)'
            : 'DATE_FORMAT(paid_at, "%Y-%m")';

        $monthlyRevenueQuery = Invoice::query();
        if (!$isAdmin) {
            $monthlyRevenueQuery->where('user_id', $user->id);
        }
        $monthlyRevenue = $monthlyRevenueQuery
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw($dateFormat . ' as month'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'revenue' => (float) $item->revenue,
                ];
            });

        return response()->json([
            'statistics' => [
                'invoices' => [
                    'total' => $totalInvoices,
                    'paid' => $paidInvoices,
                    'pending' => $pendingInvoices,
                    'overdue' => $overdueInvoices,
                ],
                'financial' => [
                    'total_amount' => (float) $totalAmount,
                    'paid_amount' => (float) $paidAmount,
                    'pending_amount' => (float) $pendingAmount,
                    'formatted_total' => '₦' . number_format($totalAmount, 2),
                    'formatted_paid' => '₦' . number_format($paidAmount, 2),
                    'formatted_pending' => '₦' . number_format($pendingAmount, 2),
                ],
                'customers' => [
                    'total' => $totalCustomers,
                ],
            ],
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $recentPayments,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }
}
