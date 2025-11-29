<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('user_id', auth()->id())
                    ->where('status', 'overdue')
                    ->with(['customer'])
                    ->orderBy('due_date', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn ($record) => url("/inv/{$record->public_id}"))
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->color('danger')
                    ->description(fn ($record) => now()->diffInDays($record->due_date) . ' days overdue'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('amount_remaining')
                    ->label('Balance Due')
                    ->money('NGN')
                    ->state(fn ($record) => $record->total_amount - $record->amount_paid)
                    ->color('danger')
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['danger' => 'overdue'])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->heading('Overdue Invoices (Action Required)')
            ->description('Invoices that are past their due date')
            ->emptyStateHeading('No overdue invoices')
            ->emptyStateDescription('Great! All invoices are current.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
