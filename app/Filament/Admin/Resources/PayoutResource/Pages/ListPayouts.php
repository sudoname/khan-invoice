<?php

namespace App\Filament\Admin\Resources\PayoutResource\Pages;

use App\Filament\Admin\Resources\PayoutResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Payouts'),
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'PENDING')->where('requires_approval', true))
                ->badge(fn () => static::getResource()::getModel()::where('status', 'PENDING')->where('requires_approval', true)->count())
                ->badgeColor('warning'),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'PROCESSING'))
                ->badge(fn () => static::getResource()::getModel()::where('status', 'PROCESSING')->count()),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'COMPLETED')),
            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'FAILED'))
                ->badge(fn () => static::getResource()::getModel()::where('status', 'FAILED')->count())
                ->badgeColor('danger'),
        ];
    }
}
