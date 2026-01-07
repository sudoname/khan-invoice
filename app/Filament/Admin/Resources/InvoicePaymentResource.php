<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoicePaymentResource\Pages;
use App\Models\Payment\InvoicePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class InvoicePaymentResource extends Resource
{
    protected static ?string $model = InvoicePayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Payment Reconciliation';

    protected static ?string $navigationGroup = 'Payment Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('reconciliation_status', 'PENDING')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pending = static::getModel()::where('reconciliation_status', 'PENDING')->count();
        return $pending > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Payment Reference')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('fees_paid')
                            ->label('Fees Paid')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('net_received')
                            ->label('Net Received')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\Select::make('reconciliation_status')
                            ->label('Reconciliation Status')
                            ->options([
                                'PENDING' => 'Pending',
                                'MATCHED' => 'Matched',
                                'RECONCILED' => 'Reconciled',
                                'DISPUTED' => 'Disputed',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->url(fn ($record) => $record->invoice ? route('filament.admin.resources.invoices.view', $record->invoice) : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.user.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fees_paid')
                    ->label('Fees')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_received')
                    ->label('Net')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('reconciliation_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'PENDING',
                        'info' => 'MATCHED',
                        'success' => 'RECONCILED',
                        'danger' => 'DISPUTED',
                    ]),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reconciliation_status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'MATCHED' => 'Matched',
                        'RECONCILED' => 'Reconciled',
                        'DISPUTED' => 'Disputed',
                    ]),
                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        Forms\Components\DatePicker::make('paid_from')
                            ->label('Paid From'),
                        Forms\Components\DatePicker::make('paid_until')
                            ->label('Paid Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['paid_from'], fn ($query, $date) => $query->whereDate('paid_at', '>=', $date))
                            ->when($data['paid_until'], fn ($query, $date) => $query->whereDate('paid_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_reconciled')
                    ->label('Mark Reconciled')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'reconciliation_status' => 'RECONCILED',
                            'reconciled_at' => now(),
                        ]);
                    })
                    ->visible(fn ($record) => $record->reconciliation_status !== 'RECONCILED'),
                Tables\Actions\Action::make('mark_disputed')
                    ->label('Mark Disputed')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'reconciliation_status' => 'DISPUTED',
                        ]);
                    })
                    ->visible(fn ($record) => $record->reconciliation_status !== 'DISPUTED'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_reconciled')
                        ->label('Mark as Reconciled')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'reconciliation_status' => 'RECONCILED',
                                'reconciled_at' => now(),
                            ]);
                        }),
                ]),
            ])
            ->defaultSort('paid_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payment Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_reference')
                            ->label('Payment Reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Invoice Number'),
                        Infolists\Components\TextEntry::make('invoice.user.name')
                            ->label('Merchant'),
                        Infolists\Components\TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('fees_paid')
                            ->label('Fees Paid')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('net_received')
                            ->label('Net Received')
                            ->money('NGN')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method'),
                        Infolists\Components\TextEntry::make('currency'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Reconciliation')
                    ->schema([
                        Infolists\Components\TextEntry::make('reconciliation_status')
                            ->badge()
                            ->colors([
                                'warning' => 'PENDING',
                                'info' => 'MATCHED',
                                'success' => 'RECONCILED',
                                'danger' => 'DISPUTED',
                            ]),
                        Infolists\Components\TextEntry::make('reconciled_at')
                            ->dateTime()
                            ->placeholder('Not reconciled yet'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('paid_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_metadata')
                            ->label('Payment Metadata')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoicePayments::route('/'),
            'view' => Pages\ViewInvoicePayment::route('/{record}'),
            'edit' => Pages\EditInvoicePayment::route('/{record}/edit'),
        ];
    }
}
