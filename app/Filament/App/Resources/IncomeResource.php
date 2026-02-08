<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\IncomeResource\Pages;
use App\Filament\App\Resources\IncomeResource\RelationManagers;
use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Direct Income';

    protected static ?string $navigationGroup = 'Get Paid';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Record income not tied to invoices (cash sales, etc.)';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Income Details')
                    ->description('Record income that doesn\'t require an invoice')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\TextInput::make('income_number')
                            ->label('Income Number')
                            ->required()
                            ->default('Auto-generated on save')
                            ->unique(ignoreRecord: true)
                            ->helperText('Sequential number generated automatically when you save')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\DatePicker::make('income_date')
                            ->label('Income Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options(Income::getCategoryOptions())
                            ->required()
                            ->searchable()
                            ->helperText('What type of income is this?'),

                        Forms\Components\Select::make('business_profile_id')
                            ->label('Business Profile')
                            ->relationship('businessProfile', 'business_name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Which business received this income?'),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Associate with a customer'),
                    ])
                    ->columns(3)
                    ->icon('heroicon-o-document-text'),

                Forms\Components\Section::make('Amount & Payment')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $tax = floatval($get('tax_amount') ?? 0);
                                $amount = floatval($state ?? 0);
                                $set('total_amount', $amount + $tax);
                            })
                            ->helperText('Base amount (before tax)'),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('VAT/Tax Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->default(0)
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $amount = floatval($get('amount') ?? 0);
                                $tax = floatval($state ?? 0);
                                $set('total_amount', $amount + $tax);
                            })
                            ->helperText('VAT collected if applicable'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Amount + Tax'),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(Income::getPaymentMethodOptions())
                            ->required()
                            ->default('cash')
                            ->helperText('How was this income received?'),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference/Transaction Number')
                            ->maxLength(255)
                            ->helperText('Bank reference or transaction ID'),

                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options(function () {
                                return \App\Models\Currency::where('is_active', true)
                                    ->pluck('name', 'code')
                                    ->toArray();
                            })
                            ->required()
                            ->default('NGN')
                            ->searchable(),
                    ])
                    ->columns(3)
                    ->icon('heroicon-o-banknotes'),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->placeholder('Describe this income transaction...')
                            ->helperText('What was this income for?'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(3)
                            ->placeholder('Optional internal notes...')
                            ->helperText('Private notes for your records'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->icon('heroicon-o-document-plus'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('income_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('income_number')
                    ->label('Income #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Income number copied!'),

                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('From Invoice')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn ($record) => $record->invoice_id ? route('filament.app.resources.invoices.view', ['record' => $record->invoice_id]) : null)
                    ->placeholder('—')
                    ->tooltip('Auto-created from paid invoice'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->colors([
                        'success' => 'cash_sales',
                        'info' => 'service_revenue',
                        'warning' => 'product_sales',
                        'primary' => fn ($state) => $state && in_array($state, ['commission', 'consulting']),
                        'secondary' => fn ($state) => $state && in_array($state, ['interest', 'rental_income', 'refund', 'other']),
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? (Income::getCategoryOptions()[$state] ?? ucwords(str_replace('_', ' ', $state))) : '—'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->description;
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->total_amount < 0 ? 'danger' : 'success')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Income'),
                    ]),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->colors([
                        'primary' => 'bank_transfer',
                        'success' => 'cash',
                        'warning' => 'cheque',
                        'info' => 'card',
                        'secondary' => 'mobile_money',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? (Income::getPaymentMethodOptions()[$state] ?? ucwords(str_replace('_', ' ', $state))) : '—')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('businessProfile.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(Income::getCategoryOptions()),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(Income::getPaymentMethodOptions()),

                Tables\Filters\Filter::make('income_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('income_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('income_date', '<=', $date));
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Income Record')
                    ->modalDescription('Are you sure you want to delete this income record? This will affect your revenue reports.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('income_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
