<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'All Payments';

    protected static ?string $navigationGroup = 'Payment Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->url(fn ($record) => $record->invoice ? route('filament.app.resources.invoices.view', $record->invoice) : null),

                Tables\Columns\TextColumn::make('invoice.user.name')
                    ->label('Merchant/Vendor')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->invoice?->user?->email)
                    ->icon('heroicon-o-user-circle')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer (Paid By)')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->invoice?->customer?->email)
                    ->icon('heroicon-o-user')
                    ->color('success'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount Paid')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->colors([
                        'primary' => 'paystack',
                        'success' => 'bank_transfer',
                        'warning' => 'cash',
                        'info' => 'cheque',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Payment Reference')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Payment Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(fn ($record) => $record->payment_date?->diffForHumans()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('has_ledger')
                    ->label('In Ledger?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(function ($record) {
                        return \App\Models\Payment\LedgerEntry::where('invoice_id', $record->invoice_id)
                            ->where('entry_type', 'PAYMENT_RECEIVED')
                            ->exists();
                    })
                    ->tooltip(fn ($record, $state) => $state ? 'Payment recorded in ledger' : 'WARNING: Payment NOT in ledger!'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'paystack' => 'Paystack',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'cheque' => 'Cheque',
                    ]),

                Tables\Filters\SelectFilter::make('merchant')
                    ->label('Merchant/Vendor')
                    ->relationship('invoice.user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($query, $date) => $query->whereDate('payment_date', '>=', $date))
                            ->when($data['until'], fn ($query, $date) => $query->whereDate('payment_date', '<=', $date));
                    }),

                Tables\Filters\Filter::make('missing_ledger')
                    ->label('Missing from Ledger')
                    ->query(function (Builder $query): Builder {
                        return $query->whereDoesntHave('invoice', function ($q) {
                            $q->whereHas('ledgerEntries', function ($q2) {
                                $q2->where('entry_type', 'PAYMENT_RECEIVED');
                            });
                        });
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('view_invoice')
                    ->label('View Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->url(fn ($record) => $record->invoice ? route('filament.app.resources.invoices.view', $record->invoice) : null)
                    ->visible(fn ($record) => $record->invoice !== null),

                Tables\Actions\Action::make('view_merchant')
                    ->label('View Merchant')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->url(fn ($record) => $record->invoice?->user ? route('filament.admin.resources.users.view', $record->invoice->user) : null)
                    ->visible(fn ($record) => $record->invoice?->user !== null),

                Tables\Actions\Action::make('check_ledger')
                    ->label('Check Ledger')
                    ->icon('heroicon-o-book-open')
                    ->color('warning')
                    ->action(function ($record) {
                        $ledgerExists = \App\Models\Payment\LedgerEntry::where('invoice_id', $record->invoice_id)
                            ->where('entry_type', 'PAYMENT_RECEIVED')
                            ->exists();

                        if ($ledgerExists) {
                            \Filament\Notifications\Notification::make()
                                ->title('Ledger Entry Found')
                                ->success()
                                ->body('This payment has a corresponding ledger entry.')
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Ledger Entry Missing!')
                                ->danger()
                                ->body('This payment does NOT have a ledger entry. This is an accounting discrepancy.')
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export functionality can be added here
                        }),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Payment ID'),

                        Infolists\Components\TextEntry::make('amount')
                            ->label('Amount Paid')
                            ->money('NGN')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge(),

                        Infolists\Components\TextEntry::make('reference_number')
                            ->label('Payment Reference')
                            ->copyable()
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('payment_date')
                            ->label('Payment Date')
                            ->date('F d, Y')
                            ->description(fn ($record) => $record->payment_date?->diffForHumans()),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Invoice Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Invoice Number')
                            ->url(fn ($record) => $record->invoice ? route('filament.app.resources.invoices.view', $record->invoice) : null),

                        Infolists\Components\TextEntry::make('invoice.total_amount')
                            ->label('Invoice Total')
                            ->money('NGN'),

                        Infolists\Components\TextEntry::make('invoice.status')
                            ->label('Invoice Status')
                            ->badge(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Merchant Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice.user.name')
                            ->label('Merchant/Vendor Name')
                            ->url(fn ($record) => $record->invoice?->user ? route('filament.admin.resources.users.view', $record->invoice->user) : null),

                        Infolists\Components\TextEntry::make('invoice.user.email')
                            ->label('Merchant Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('invoice.user.id')
                            ->label('Merchant User ID'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('invoice.customer.name')
                            ->label('Customer Name (Paid By)'),

                        Infolists\Components\TextEntry::make('invoice.customer.email')
                            ->label('Customer Email')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('invoice.customer.phone')
                            ->label('Customer Phone'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Ledger Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('ledger_status')
                            ->label('Ledger Entry Status')
                            ->badge()
                            ->color(fn ($record) => \App\Models\Payment\LedgerEntry::where('invoice_id', $record->invoice_id)
                                ->where('entry_type', 'PAYMENT_RECEIVED')
                                ->exists() ? 'success' : 'danger')
                            ->getStateUsing(function ($record) {
                                $exists = \App\Models\Payment\LedgerEntry::where('invoice_id', $record->invoice_id)
                                    ->where('entry_type', 'PAYMENT_RECEIVED')
                                    ->exists();
                                return $exists ? '✓ Recorded in Ledger' : '✗ MISSING FROM LEDGER';
                            }),

                        Infolists\Components\TextEntry::make('ledger_details')
                            ->label('Ledger Details')
                            ->getStateUsing(function ($record) {
                                $ledger = \App\Models\Payment\LedgerEntry::where('invoice_id', $record->invoice_id)
                                    ->where('entry_type', 'PAYMENT_RECEIVED')
                                    ->first();

                                if ($ledger) {
                                    return "Amount: ₦" . number_format($ledger->amount, 2) .
                                           " | Balance After: ₦" . number_format($ledger->balance_after, 2) .
                                           " | Date: " . $ledger->entry_date->format('M d, Y H:i');
                                }

                                return 'No ledger entry found for this payment';
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Additional Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Payment Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Recorded At')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Payments should only be created through the payment gateway
    }

    public static function canEdit($record): bool
    {
        return false; // Payments should not be edited
    }

    public static function canDelete($record): bool
    {
        return false; // Payments should not be deleted
    }
}
