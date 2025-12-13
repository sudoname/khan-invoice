<?php

namespace App\Filament\App\Pages;

use App\Models\PaymentTransaction;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;

class PaymentHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.app.pages.payment-history';
    protected static ?string $navigationLabel = 'Payment History';
    protected static ?string $title = 'Payment History';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int $navigationSort = 92;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PaymentTransaction::query()
                    ->where('user_id', auth()->id())
                    ->with('subscription')
                    ->latest()
            )
            ->columns([
                TextColumn::make('transaction_reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->weight('medium'),

                TextColumn::make('formatted_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'upgrade') => 'success',
                        str_contains(strtolower($state), 'downgrade') => 'warning',
                        str_contains(strtolower($state), 'subscription') => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->weight('bold')
                    ->color('success'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'successful',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'successful',
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-arrow-uturn-left' => 'refunded',
                    ]),

                TextColumn::make('payment_gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'successful' => 'Successful',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'subscription_payment' => 'Subscription Payment',
                        'credit_purchase' => 'Credit Purchase',
                        'upgrade' => 'Upgrade',
                        'downgrade' => 'Downgrade',
                    ]),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Transaction Details')
                    ->modalContent(fn (PaymentTransaction $record): \Illuminate\Contracts\View\View => view(
                        'filament.app.pages.payment-transaction-details',
                        ['transaction' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }

    public function getTotalAmount(): string
    {
        $total = PaymentTransaction::where('user_id', auth()->id())
            ->where('status', 'successful')
            ->sum('amount');

        return '¦' . number_format($total, 2);
    }

    public function getSuccessfulCount(): int
    {
        return PaymentTransaction::where('user_id', auth()->id())
            ->where('status', 'successful')
            ->count();
    }

    public function getThisMonthAmount(): string
    {
        $amount = PaymentTransaction::where('user_id', auth()->id())
            ->where('status', 'successful')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return '¦' . number_format($amount, 2);
    }
}
