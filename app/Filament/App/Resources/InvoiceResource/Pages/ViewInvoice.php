<?php

namespace App\Filament\App\Resources\InvoiceResource\Pages;

use App\Filament\App\Resources\InvoiceResource;
use App\Models\PaymentSetting;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Support\Enums\FontWeight;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Invoice Information')
                    ->schema([
                        Components\TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->weight(FontWeight::Bold),
                        Components\TextEntry::make('customer.name')
                            ->label('Customer'),
                        Components\TextEntry::make('businessProfile.business_name')
                            ->label('Business Profile'),
                        Components\TextEntry::make('issue_date')
                            ->date(),
                        Components\TextEntry::make('due_date')
                            ->date(),
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'sent' => 'warning',
                                'paid' => 'success',
                                'partially_paid' => 'info',
                                'overdue' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                        Components\TextEntry::make('currency')
                            ->label('Currency'),
                    ])
                    ->columns(2),

                Components\Section::make('Financial Summary')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('subtotal')
                                    ->money('NGN')
                                    ->weight(FontWeight::Bold),
                                Components\TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('NGN')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                                Components\TextEntry::make('amount_paid')
                                    ->label('Amount Paid')
                                    ->money('NGN'),
                                Components\TextEntry::make('amount_remaining')
                                    ->label('Amount Remaining')
                                    ->money('NGN')
                                    ->state(fn ($record): float => $record->total_amount - $record->amount_paid)
                                    ->color(fn ($record): string => $record->total_amount - $record->amount_paid > 0 ? 'warning' : 'success'),
                            ]),
                    ]),

                Components\Section::make('Payment Fee Breakdown')
                    ->description('When your customer pays via card/online payment, here\'s how fees are distributed')
                    ->schema([
                        Components\ViewField::make('fee_breakdown')
                            ->view('filament.app.infolists.fee-breakdown')
                            ->state(fn ($record) => [
                                'invoice_amount' => $record->total_amount,
                                'breakdown' => PaymentSetting::calculateNetAmountReceived($record->total_amount),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Components\Section::make('Line Items')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Components\TextEntry::make('description')
                                    ->label('Description'),
                                Components\TextEntry::make('quantity')
                                    ->label('Qty'),
                                Components\TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('NGN'),
                                Components\TextEntry::make('total')
                                    ->label('Total')
                                    ->money('NGN')
                                    ->weight(FontWeight::Bold),
                            ])
                            ->columns(4)
                            ->grid(4),
                    ]),

                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                        Components\TextEntry::make('footer_text')
                            ->label('Footer Text')
                            ->placeholder('No footer text')
                            ->columnSpanFull(),
                        Components\TextEntry::make('public_id')
                            ->label('Public Link')
                            ->url(fn ($record): string => url("/inv/{$record->public_id}"))
                            ->openUrlInNewTab()
                            ->copyable()
                            ->copyMessage('Link copied!')
                            ->formatStateUsing(fn ($record) => url("/inv/{$record->public_id}")),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\Action::make('view_public')
                ->label('View Public Link')
                ->icon('heroicon-o-eye')
                ->url(fn ($record): string => url("/inv/{$record->public_id}"))
                ->openUrlInNewTab(),
        ];
    }
}
