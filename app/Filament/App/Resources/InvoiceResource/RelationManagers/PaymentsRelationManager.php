<?php

namespace App\Filament\App\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payment History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Payment Amount')
                    ->required()
                    ->numeric()
                    ->prefix('₦')
                    ->minValue(0.01)
                    ->default(fn ($livewire) => $livewire->ownerRecord->total_amount - $livewire->ownerRecord->amount_paid),

                Forms\Components\DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->required()
                    ->default(now())
                    ->maxDate(now()),

                Forms\Components\Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'cheque' => 'Cheque',
                        'card' => 'Card Payment',
                        'mobile_money' => 'Mobile Money',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('bank_transfer'),

                Forms\Components\TextInput::make('reference_number')
                    ->label('Reference Number')
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->label('Payment Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->colors([
                        'primary' => 'bank_transfer',
                        'success' => 'cash',
                        'warning' => 'cheque',
                        'info' => 'card',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->copyable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Payment')
                    ->successNotificationTitle('Payment recorded successfully')
                    ->after(function () {
                        // Notification is automatically shown by observer
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->successNotificationTitle('Payment deleted successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Record your first payment using the button above.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
