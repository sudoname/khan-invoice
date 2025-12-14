<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Get payment transaction history for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = PaymentTransaction::where('user_id', $request->user()->id)
            ->with(['subscription.plan']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $payments = $query->latest()->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    /**
     * Get a specific payment transaction.
     */
    public function show(Request $request, string $id)
    {
        $payment = PaymentTransaction::where('user_id', $request->user()->id)
            ->with(['subscription.plan'])
            ->findOrFail($id);

        return new PaymentResource($payment);
    }
}
