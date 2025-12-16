<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BusinessProfileResource;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessProfileController extends Controller
{
    public function index(Request $request)
    {
        $profiles = BusinessProfile::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return BusinessProfileResource::collection($profiles);
    }

    public function show(Request $request, string $id)
    {
        $profile = BusinessProfile::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return new BusinessProfileResource($profile);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'cac_number' => 'nullable|string|max:50',
            'tin' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:20',
            'bank_account_type' => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|max:3',
            'default_vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['user_id'] = $request->user()->id;

        $profile = BusinessProfile::create($validated);

        Log::info('Business profile created via API', [
            'user_id' => $request->user()->id,
            'profile_id' => $profile->id,
        ]);

        return (new BusinessProfileResource($profile))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $id)
    {
        $profile = BusinessProfile::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'business_name' => 'string|max:255',
            'cac_number' => 'nullable|string|max:50',
            'tin' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:20',
            'bank_account_type' => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|max:3',
            'default_vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $profile->update($validated);

        Log::info('Business profile updated via API', [
            'user_id' => $request->user()->id,
            'profile_id' => $profile->id,
        ]);

        return new BusinessProfileResource($profile);
    }

    public function destroy(Request $request, string $id)
    {
        $profile = BusinessProfile::where('user_id', $request->user()->id)->findOrFail($id);

        $profile->delete();

        Log::info('Business profile deleted via API', [
            'user_id' => $request->user()->id,
            'profile_id' => $id,
        ]);

        return response()->json(['message' => 'Business profile deleted successfully'], 200);
    }
}
