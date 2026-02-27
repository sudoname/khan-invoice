<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('check_ledger_integrity')
                ->label('Check Ledger Integrity')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action(function () {
                    $paymentsWithoutLedger = \App\Models\Payment::whereDoesntHave('invoice', function ($q) {
                        $q->whereHas('ledgerEntries', function ($q2) {
                            $q2->where('entry_type', 'PAYMENT_RECEIVED');
                        });
                    })->count();

                    $totalPayments = \App\Models\Payment::count();
                    $paymentsWithLedger = $totalPayments - $paymentsWithoutLedger;

                    if ($paymentsWithoutLedger > 0) {
                        Notification::make()
                            ->title('Ledger Integrity Issues Found')
                            ->warning()
                            ->body("Found {$paymentsWithoutLedger} payments (out of {$totalPayments}) without ledger entries. Use the 'Missing from Ledger' filter to see them.")
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Ledger Integrity Check Passed')
                            ->success()
                            ->body("All {$totalPayments} payments have corresponding ledger entries.")
                            ->send();
                    }
                }),

            Actions\Action::make('export_all')
                ->label('Export All Payments')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    Notification::make()
                        ->title('Export functionality coming soon')
                        ->info()
                        ->send();
                }),
        ];
    }
}
