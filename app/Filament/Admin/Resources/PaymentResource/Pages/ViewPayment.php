<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_invoice')
                ->label('View Invoice')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn () => $this->record->invoice ? route('filament.app.resources.invoices.view', $this->record->invoice) : null)
                ->visible(fn () => $this->record->invoice !== null),

            Actions\Action::make('view_merchant')
                ->label('View Merchant')
                ->icon('heroicon-o-user-circle')
                ->color('info')
                ->url(fn () => $this->record->invoice?->user ? route('filament.admin.resources.users.view', $this->record->invoice->user) : null)
                ->visible(fn () => $this->record->invoice?->user !== null),

            Actions\Action::make('check_ledger')
                ->label('Verify Ledger Entry')
                ->icon('heroicon-o-book-open')
                ->color('warning')
                ->action(function () {
                    $ledger = \App\Models\Payment\LedgerEntry::where('invoice_id', $this->record->invoice_id)
                        ->where('entry_type', 'PAYMENT_RECEIVED')
                        ->first();

                    if ($ledger) {
                        Notification::make()
                            ->title('Ledger Entry Found')
                            ->success()
                            ->body("Payment recorded in ledger: ₦" . number_format($ledger->amount, 2) . " | Balance after: ₦" . number_format($ledger->balance_after, 2))
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Ledger Entry Missing!')
                            ->danger()
                            ->body('This payment does NOT have a ledger entry. This is a critical accounting discrepancy that should be investigated.')
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('create_ledger_entry')
                                    ->button()
                                    ->label('Create Ledger Entry')
                                    ->color('success')
                                    ->action(function () {
                                        Notification::make()
                                            ->title('Manual ledger creation')
                                            ->warning()
                                            ->body('Please contact the development team to properly backfill this ledger entry.')
                                            ->send();
                                    }),
                            ])
                            ->send();
                    }
                }),

            Actions\Action::make('copy_reference')
                ->label('Copy Reference')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('Reference Copied')
                        ->body('Payment reference: ' . $this->record->reference_number)
                        ->success()
                        ->send();
                }),
        ];
    }
}
