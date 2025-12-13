<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\InvoiceResource\Pages;
use App\Filament\App\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('business_profile_id')
                            ->label('Business Profile')
                            ->relationship('businessProfile', 'business_name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('business_name')->required(),
                                Forms\Components\TextInput::make('email')->email(),
                                Forms\Components\TextInput::make('phone'),
                                Forms\Components\Textarea::make('address'),
                            ])
                            ->helperText('Select the business profile for this invoice'),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('company_name'),
                                Forms\Components\TextInput::make('email')->email(),
                                Forms\Components\TextInput::make('phone'),
                            ]),
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->default(fn () => \App\Models\Invoice::generateInvoiceNumber())
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated sequential number'),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDays(30)),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'partially_paid' => 'Partially Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft'),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options(function () {
                                return \App\Models\Currency::where('is_active', true)
                                    ->pluck('name', 'code')
                                    ->toArray();
                            })
                            ->required()
                            ->default('USD')
                            ->searchable()
                            ->helperText('Select invoice currency - 53+ currencies supported'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01),
                                Forms\Components\TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₦'),
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₦'),
                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Total')
                                    ->content(function (Get $get) {
                                        $quantity = floatval($get('quantity') ?: 0);
                                        $price = floatval($get('unit_price') ?: 0);
                                        $discount = floatval($get('discount') ?: 0);

                                        // Line total WITHOUT per-item tax (VAT is applied at invoice level)
                                        $total = ($quantity * $price) - $discount;

                                        return '₦' . number_format($total, 2);
                                    }),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->addActionLabel('Add Line Item')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Tax & Totals')
                    ->schema([
                        Forms\Components\TextInput::make('discount_total')
                            ->label('Invoice Discount')
                            ->numeric()
                            ->default(0)
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('vat_rate')
                            ->label('VAT Rate (%)')
                            ->required()
                            ->numeric()
                            ->default(7.5)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('wht_rate')
                            ->label('Withholding Tax Rate (%)')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('WHT will be deducted from total'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Already Paid')
                            ->numeric()
                            ->default(0)
                            ->prefix('₦'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Private Notes')
                            ->helperText('Internal notes (not shown on invoice)')
                            ->rows(3),
                        Forms\Components\Textarea::make('footer')
                            ->label('Invoice Footer')
                            ->helperText('Text shown at bottom of invoice (e.g., payment terms)')
                            ->rows(3),
                    ])
                    ->columns(2)
                    ->collapsible(),
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Issued')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Invoiced'),
                    ]),
                Tables\Columns\TextColumn::make('amount_remaining')
                    ->label('Amount Remaining')
                    ->money('NGN')
                    ->sortable()
                    ->state(fn (Invoice $record): float => $record->total_amount - $record->amount_paid)
                    ->color(fn (Invoice $record): string => $record->total_amount - $record->amount_paid > 0 ? 'warning' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'info' => 'partially_paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('NGN')
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Paid'),
                    ]),
                Tables\Columns\TextColumn::make('public_id')
                    ->label('Public Link')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('Link copied!')
                    ->formatStateUsing(fn ($state) => url("/inv/{$state}")),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now())->whereNotIn('status', ['paid', 'cancelled']))
                    ->label('Overdue Invoices'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_public')
                    ->label('View Public')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Invoice $record): string => url("/inv/{$record->public_id}"))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_invoice')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice')
                    ->modalDescription(fn (Invoice $record) => "Send invoice {$record->invoice_number} to {$record->customer->name}?")
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'sent']);
                        // Here you can add email sending logic later
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice sent')
                            ->body('Invoice has been marked as sent.')
                    )
                    ->visible(fn (Invoice $record): bool => $record->status === 'draft'),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->update([
                            'status' => 'paid',
                            'amount_paid' => $record->total_amount
                        ]);
                    })
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Admin users can see all invoices, members only see their own
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
