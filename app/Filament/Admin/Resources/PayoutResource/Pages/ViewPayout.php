<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load(['user', 'merchantAccount']);
    }
}
