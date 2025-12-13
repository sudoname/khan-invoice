<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get sales report.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sales(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'currency' => 'string|max:3',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $userId = $request->user()->id;

        // Build query
        $query = Invoice::where('user_id', $userId)
            ->whereBetween('issue_date', [$startDate, $endDate]);

        if ($request->currency) {
            $query->where('currency', $request->currency);
        }

        // Get invoices
        $invoices = $query->get();

        // Calculate totals
        $totalInvoices = $invoices->count();
        $totalAmount = $invoices->sum('total_amount');
        $paidAmount = $invoices->where('status', 'paid')->sum('total_amount');
        $unpaidAmount = $invoices->whereIn('status', ['sent', 'partially_paid', 'overdue'])->sum('total_amount');

        // Group by status
        $byStatus = $invoices->groupBy('status')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('total_amount'),
            ];
        });

        // Group by currency
        $byCurrency = $invoices->groupBy('currency')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('total_amount'),
            ];
        });

        return response()->json([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => $unpaidAmount,
            ],
            'by_status' => $byStatus,
            'by_currency' => $byCurrency,
            'invoices' => $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name,
                    'issue_date' => $invoice->issue_date->toDateString(),
                    'due_date' => $invoice->due_date->toDateString(),
                    'status' => $invoice->status,
                    'currency' => $invoice->currency,
                    'total_amount' => $invoice->total_amount,
                ];
            }),
        ]);
    }

    /**
     * Get aging report (accounts receivable).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function aging(Request $request)
    {
        $userId = $request->user()->id;
        $today = Carbon::today();

        // Get all unpaid and partially paid invoices
        $invoices = Invoice::where('user_id', $userId)
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->with('customer')
            ->get();

        // Categorize by age
        $current = [];
        $days1to30 = [];
        $days31to60 = [];
        $days61to90 = [];
        $over90 = [];

        foreach ($invoices as $invoice) {
            $daysOverdue = $today->diffInDays($invoice->due_date, false);
            $invoiceData = [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name,
                'issue_date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'days_overdue' => -$daysOverdue,
                'currency' => $invoice->currency,
                'amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'balance' => $invoice->total_amount - ($invoice->paid_amount ?? 0),
            ];

            if ($daysOverdue >= 0) {
                $current[] = $invoiceData;
            } elseif ($daysOverdue >= -30) {
                $days1to30[] = $invoiceData;
            } elseif ($daysOverdue >= -60) {
                $days31to60[] = $invoiceData;
            } elseif ($daysOverdue >= -90) {
                $days61to90[] = $invoiceData;
            } else {
                $over90[] = $invoiceData;
            }
        }

        return response()->json([
            'as_of_date' => $today->toDateString(),
            'summary' => [
                'current' => [
                    'count' => count($current),
                    'total' => collect($current)->sum('balance'),
                ],
                '1-30_days' => [
                    'count' => count($days1to30),
                    'total' => collect($days1to30)->sum('balance'),
                ],
                '31-60_days' => [
                    'count' => count($days31to60),
                    'total' => collect($days31to60)->sum('balance'),
                ],
                '61-90_days' => [
                    'count' => count($days61to90),
                    'total' => collect($days61to90)->sum('balance'),
                ],
                'over_90_days' => [
                    'count' => count($over90),
                    'total' => collect($over90)->sum('balance'),
                ],
            ],
            'details' => [
                'current' => $current,
                '1-30_days' => $days1to30,
                '31-60_days' => $days31to60,
                '61-90_days' => $days61to90,
                'over_90_days' => $over90,
            ],
        ]);
    }

    /**
     * Get profit and loss statement.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profitLoss(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $userId = $request->user()->id;

        // Get paid invoices (revenue)
        $revenue = Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->get();

        $totalRevenue = $revenue->sum('total_amount');

        // Group revenue by currency
        $revenueByCurrency = $revenue->groupBy('currency')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('total_amount'),
            ];
        });

        // Get payments received
        $payments = Payment::whereHas('invoice', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereBetween('payment_date', [$startDate, $endDate])
        ->with('invoice')
        ->get();

        $totalPayments = $payments->sum('amount');

        // Calculate costs (for now, we'll use payment processing fees if available)
        // In a full system, you'd have expense tracking
        $paymentFees = $payments->sum('fee') ?? 0;
        $smsCosts = DB::table('sms_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('cost') ?? 0;

        $whatsappCosts = DB::table('whatsapp_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('cost') ?? 0;

        $totalCosts = $paymentFees + $smsCosts + $whatsappCosts;
        $netProfit = $totalRevenue - $totalCosts;

        return response()->json([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'income' => [
                'total_revenue' => $totalRevenue,
                'total_payments_received' => $totalPayments,
                'by_currency' => $revenueByCurrency,
            ],
            'expenses' => [
                'payment_processing_fees' => $paymentFees,
                'sms_costs' => $smsCosts,
                'whatsapp_costs' => $whatsappCosts,
                'total_expenses' => $totalCosts,
            ],
            'net_profit' => $netProfit,
            'profit_margin' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0,
        ]);
    }
}
