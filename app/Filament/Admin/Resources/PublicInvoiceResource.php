<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PublicInvoiceResource\Pages;
use App\Models\PublicInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicInvoiceResource extends Resource
{
    protected static ?string $model = PublicInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Public Invoices';

    protected static ?string $navigationGroup = 'Invoice Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('public_id')
                            ->label('Public ID')
                            ->disabled()
                            ->helperText('Unique identifier for public access'),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->disabled(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('From (Business)')
                    ->schema([
                        Forms\Components\TextInput::make('from_name')
                            ->label('Business Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_phone')
                            ->label('Phone')
                            ->disabled(),
                        Forms\Components\Textarea::make('from_address')
                            ->label('Address')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Bank Details')
                    ->schema([
                        Forms\Components\TextInput::make('from_bank_name')
                            ->label('Bank Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_account_number')
                            ->label('Account Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_account_name')
                            ->label('Account Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_account_type')
                            ->label('Account Type')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('To (Customer)')
                    ->schema([
                        Forms\Components\TextInput::make('to_name')
                            ->label('Customer Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('to_email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('to_phone')
                            ->label('Phone')
                            ->disabled(),
                        Forms\Components\Textarea::make('to_address')
                            ->label('Address')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('vat_percentage')
                            ->label('VAT %')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('vat_amount')
                            ->label('VAT Amount')
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('wht_percentage')
                            ->label('WHT %')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('wht_amount')
                            ->label('WHT Amount')
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Discount %')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->prefix('₦')
                            ->disabled(),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Payment Status')
                    ->schema([
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'partially_paid' => 'Partially Paid',
                                'overdue' => 'Overdue',
                            ])
                            ->default('pending'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->prefix('₦')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('from_name')
                    ->label('From')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('to_name')
                    ->label('To')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'primary' => 'partially_paid',
                        'danger' => 'overdue',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\Filter::make('issue_date')
                    ->form([
                        Forms\Components\DatePicker::make('issued_from')
                            ->label('Issued From'),
                        Forms\Components\DatePicker::make('issued_until')
                            ->label('Issued Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['issued_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['issued_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_public')
                    ->label('View Public')
                    ->icon('heroicon-o-globe-alt')
                    ->color('info')
                    ->url(fn (PublicInvoice $record): string => route('public-invoice.show', $record->public_id))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (PublicInvoice $record): string => route('public-invoice.download', $record->public_id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicInvoices::route('/'),
            'view' => Pages\ViewPublicInvoice::route('/{record}'),
            'edit' => Pages\EditPublicInvoice::route('/{record}/edit'),
        ];
    }
}
