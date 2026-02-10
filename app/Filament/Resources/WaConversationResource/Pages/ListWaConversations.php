<?php

namespace App\Filament\Resources\WaConversationResource\Pages;

use App\Filament\Resources\WaConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaConversations extends ListRecords
{
    protected static string $resource = WaConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->url())),
        ];
    }
}
