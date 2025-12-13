<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(min($request->get('per_page', 15), 100));

        return response()->json($customers);
    }

    public function show(Request $request, string $id)
    {
        $customer = Customer::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($customer);
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

        return response()->json($customer, 201);
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

        return response()->json($customer);
    }

    public function destroy(Request $request, string $id)
    {
        $customer = Customer::where('user_id', $request->user()->id)->findOrFail($id);
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
