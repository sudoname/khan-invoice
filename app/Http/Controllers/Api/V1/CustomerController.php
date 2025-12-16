<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $userEmail = $request->user()->email;

        Log::info('Customers API called', [
            'user_id' => $userId,
            'user_email' => $userEmail,
        ]);

        $customers = Customer::where('user_id', $userId)
            ->latest()
            ->paginate(min($request->get('per_page', 15), 100));

        Log::info('Customers API result', [
            'user_id' => $userId,
            'total_customers' => $customers->total(),
            'returned_count' => $customers->count(),
        ]);

        // Debug: Check if there are ANY customers in the database
        $totalCustomersInDb = Customer::count();
        $customersForOtherUsers = Customer::where('user_id', '!=', $userId)->count();

        Log::info('Customer database stats', [
            'total_in_db' => $totalCustomersInDb,
            'for_other_users' => $customersForOtherUsers,
            'for_this_user' => $customers->total(),
        ]);

        return CustomerResource::collection($customers);
    }

    public function show(Request $request, string $id)
    {
        $customer = Customer::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return new CustomerResource($customer);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'tin' => 'nullable|string|max:50',
        ]);

        $validated['user_id'] = $request->user()->id;

        $customer = Customer::create($validated);

        Log::info('Customer created via API', [
            'user_id' => $request->user()->id,
            'customer_id' => $customer->id,
        ]);

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'email|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'tin' => 'nullable|string|max:50',
        ]);

        $customer->update($validated);

        Log::info('Customer updated via API', [
            'user_id' => $request->user()->id,
            'customer_id' => $customer->id,
        ]);

        return new CustomerResource($customer);
    }

    public function destroy(Request $request, string $id)
    {
        $customer = Customer::where('user_id', $request->user()->id)->findOrFail($id);
        $customer->delete();

        Log::info('Customer deleted via API', [
            'user_id' => $request->user()->id,
            'customer_id' => $id,
        ]);

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
