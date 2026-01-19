<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function resolveRecord($key): Model
    {
        try {
            $record = static::getResource()::resolveRecordRouteBinding($key);

            if (!$record) {
                Log::error('Payout record not found', ['id' => $key]);
                abort(404, 'Payout not found');
            }

            // Eager load relationships
            $record->load(['user', 'merchantAccount', 'merchantAccount.user', 'approver']);

            Log::info('Payout record loaded', [
                'id' => $record->id,
                'reference' => $record->reference,
                'status' => $record->status,
                'has_user' => $record->user !== null,
                'has_merchant_account' => $record->merchantAccount !== null,
                'has_approver' => $record->approver !== null,
                'completed_at' => $record->completed_at?->toDateTimeString(),
                'provider_response_type' => gettype($record->provider_response),
            ]);

            return $record;
        } catch (\Exception $e) {
            Log::error('Error loading payout record', [
                'id' => $key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
