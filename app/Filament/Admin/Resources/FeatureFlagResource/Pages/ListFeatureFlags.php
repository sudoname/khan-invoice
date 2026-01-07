<?php

namespace App\Filament\Admin\Resources\FeatureFlagResource\Pages;

use App\Filament\Admin\Resources\FeatureFlagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Features'),
            'enabled' => Tab::make('Enabled')
                ->modifyQueryUsing(fn ($query) => $query->where('enabled', true))
                ->badge(fn () => static::getResource()::getModel()::where('enabled', true)->count())
                ->badgeColor('success'),
            'disabled' => Tab::make('Disabled')
                ->modifyQueryUsing(fn ($query) => $query->where('enabled', false))
                ->badge(fn () => static::getResource()::getModel()::where('enabled', false)->count())
                ->badgeColor('danger'),
        ];
    }
}
