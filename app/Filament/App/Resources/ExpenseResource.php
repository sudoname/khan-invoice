<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ExpenseResource\Pages;
use App\Filament\App\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Expenses';

    protected static ?string $navigationGroup = 'My Business';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Track spending and receipts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\Section::make('Expense Details')
                    ->schema([
                        Forms\Components\Select::make('business_profile_id')
                            ->label('Business Profile')
                            ->relationship('businessProfile', 'business_name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required(),
                                Forms\Components\TextInput::make('company_name'),
                                Forms\Components\TextInput::make('email')->email(),
                                Forms\Components\TextInput::make('phone')->tel(),
                            ]),
                        Forms\Components\TextInput::make('expense_number')
                            ->default(fn () => \App\Models\Expense::generateExpenseNumber())
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->required()
                            ->options([
                                'utilities' => 'Utilities',
                                'rent' => 'Rent',
                                'salaries' => 'Salaries',
                                'supplies' => 'Office Supplies',
                                'equipment' => 'Equipment',
                                'services' => 'Professional Services',
                                'travel' => 'Travel',
                                'marketing' => 'Marketing',
                                'insurance' => 'Insurance',
                                'taxes' => 'Taxes',
                                'other' => 'Other',
                            ])
                            ->searchable(),
                        Forms\Components\DatePicker::make('expense_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date'),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Vendor Invoice/Reference #'),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Amount')
                    ->schema([
                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->default('NGN')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Tax Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('₦'),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'debit_card' => 'Debit Card',
                            ]),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('receipt_url')
                            ->label('Receipt/Proof of Payment')
                            ->image()
                            ->maxSize(5120),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('expense_number')
                    ->label('Expense #')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->description;
                    }),

                Tables\Columns\TextColumn::make('receivedInvoice.invoice_number')
                    ->label('Invoice Received')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => $record->received_invoice_id ? route('filament.app.resources.invoices.view', $record->received_invoice_id) : null)
                    ->placeholder('—')
                    ->description(fn ($record) => $record->receivedInvoice ? 'From: ' . ($record->receivedInvoice->businessProfile->business_name ?? $record->receivedInvoice->user->name) : null),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'primary' => 'services',
                        'success' => 'supplies',
                        'warning' => 'utilities',
                        'danger' => 'rent',
                        'info' => fn ($state) => in_array($state, ['salary', 'wages']),
                    ]),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Expenses'),
                    ]),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->due_date && $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Paid Via')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not paid'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'rent' => 'Rent',
                        'utilities' => 'Utilities',
                        'supplies' => 'Office Supplies',
                        'equipment' => 'Equipment',
                        'salary' => 'Salary',
                        'wages' => 'Wages',
                        'services' => 'Professional Services',
                        'travel' => 'Travel',
                        'marketing' => 'Marketing',
                        'insurance' => 'Insurance',
                        'taxes' => 'Taxes',
                        'other' => 'Other',
                    ]),

                Tables\Filters\Filter::make('received_invoices')
                    ->label('Received Invoices Only')
                    ->query(fn (Builder $query) => $query->whereNotNull('received_invoice_id')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_invoice')
                    ->label('View Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record) => $record->received_invoice_id ? route('filament.app.resources.invoices.view', $record->received_invoice_id) : null)
                    ->visible(fn ($record) => $record->received_invoice_id !== null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Admin users can see all expenses, members only see their own
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
