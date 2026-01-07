<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PayoutResource\Pages;
use App\Models\Payment\Payout;
use App\Models\Payment\MerchantAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $navigationGroup = 'Get Paid';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('user_id', auth()->id())
            ->where('status', 'PENDING')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Available Balance')
                    ->description('Your current available balance for withdrawal')
                    ->schema([
                        Forms\Components\Placeholder::make('current_balance')
                            ->label('')
                            ->content(function () {
                                $account = MerchantAccount::where('user_id', auth()->id())
                                    ->where('is_primary', true)
                                    ->where('is_active', true)
                                    ->where('verification_status', 'VERIFIED')
                                    ->first();

                                if (!$account) {
                                    return new HtmlString('
                                        <div class="text-red-600 font-semibold">
                                            ⚠️ No verified bank account found. Please add and verify a bank account first.
                                        </div>
                                    ');
                                }

                                $balance = $account->getAvailableBalance();

                                return new HtmlString('
                                    <div class="flex items-center justify-between bg-green-50 rounded-lg p-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Available Balance</p>
                                            <p class="text-3xl font-bold text-green-700">₦' . number_format($balance, 2) . '</p>
                                            <p class="text-xs text-gray-500 mt-1">Account: ' . $account->bank_name . ' - ' . $account->account_number . '</p>
                                        </div>
                                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                ');
                            }),
                    ])
                    ->visible(fn () => !request()->routeIs('*.edit'))
                    ->columnSpanFull(),

                Forms\Components\Section::make('Payout Request Details')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\Hidden::make('reference')
                            ->default(fn () => Payout::generateReference('MANUAL')),

                        Forms\Components\Select::make('merchant_account_id')
                            ->label('Bank Account')
                            ->options(function () {
                                return MerchantAccount::where('user_id', auth()->id())
                                    ->where('is_active', true)
                                    ->where('verification_status', 'VERIFIED')
                                    ->get()
                                    ->mapWithKeys(fn ($account) =>
                                        [$account->id => "{$account->bank_name} - {$account->account_number} ({$account->account_name})"]
                                    );
                            })
                            ->required()
                            ->searchable()
                            ->helperText('Select the bank account to receive this payout')
                            ->disabled(fn () => request()->routeIs('*.edit')),

                        Forms\Components\Select::make('payout_type')
                            ->label('Payout Type')
                            ->options(function () {
                                $options = [
                                    'MANUAL' => 'Manual (Free, 1-3 business days)',
                                    'STANDARD' => 'Standard (Free, T+1)',
                                ];

                                // Only show instant payout option if feature flag is enabled
                                if (\App\Models\FeatureFlag::isEnabledForEnvironment('instant_payouts')) {
                                    $options['INSTANT'] = 'Instant (2% fee, within 1 hour) ⚡';
                                }

                                return $options;
                            })
                            ->required()
                            ->default('MANUAL')
                            ->live()
                            ->helperText(function () {
                                if (\App\Models\FeatureFlag::isEnabledForEnvironment('instant_payouts')) {
                                    return 'Choose your payout speed';
                                }
                                return 'Instant payouts are currently unavailable. Contact support to enable this premium feature.';
                            })
                            ->disabled(fn () => request()->routeIs('*.edit')),

                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Amount to Withdraw')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(500)
                            ->helperText('Minimum withdrawal: ₦500')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $amount = (float) ($state ?? 0);
                                $type = $get('payout_type');

                                // Calculate fee
                                $fee = 0;
                                if ($type === 'INSTANT' && $amount > 0) {
                                    $fee = $amount * 0.02; // 2% instant fee
                                }

                                $net = $amount - $fee;

                                $set('payout_fee', $fee);
                                $set('net_amount', $net);
                            })
                            ->disabled(fn () => request()->routeIs('*.edit')),

                        Forms\Components\TextInput::make('payout_fee')
                            ->label('Fee')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Processing fee (if any)'),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('You Will Receive')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Amount after fees')
                            ->extraAttributes(['class' => 'font-bold text-green-700']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(fn ($record) => $record ? match($record->status) {
                                'PENDING' => '⏳ Pending Approval',
                                'PROCESSING' => '⚙️ Processing',
                                'COMPLETED' => '✅ Completed',
                                'FAILED' => '❌ Failed',
                                'REVERSED' => '↩️ Reversed',
                                default => $record->status
                            } : 'New Request'),

                        Forms\Components\Placeholder::make('initiated_at')
                            ->label('Initiated At')
                            ->content(fn ($record) => $record?->initiated_at ? $record->initiated_at->format('M d, Y g:i A') : 'Not yet')
                            ->visible(fn ($record) => $record?->initiated_at),

                        Forms\Components\Placeholder::make('completed_at')
                            ->label('Completed At')
                            ->content(fn ($record) => $record?->completed_at ? $record->completed_at->format('M d, Y g:i A') : 'Not yet')
                            ->visible(fn ($record) => $record?->completed_at),

                        Forms\Components\Placeholder::make('failure_reason')
                            ->label('Failure Reason')
                            ->content(fn ($record) => $record?->failure_reason ?? 'N/A')
                            ->visible(fn ($record) => $record?->status === 'FAILED'),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('merchantAccount.bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->limit(20)
                    ->description(fn ($record) => $record->merchantAccount->account_number),

                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->weight('bold')
                    ->description(fn ($record) => 'Net: ₦' . number_format($record->net_amount, 2)),

                Tables\Columns\TextColumn::make('payout_fee')
                    ->label('Fee')
                    ->money('NGN')
                    ->toggleable()
                    ->color('warning'),

                Tables\Columns\BadgeColumn::make('payout_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'INSTANT',
                        'info' => 'STANDARD',
                        'gray' => 'MANUAL',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(strtolower($state))),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PENDING',
                        'info' => 'PROCESSING',
                        'success' => 'COMPLETED',
                        'danger' => 'FAILED',
                        'gray' => 'REVERSED',
                    ]),

                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Approval')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->needsApproval() ? 'Pending approval' : 'No approval needed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Pending'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'PROCESSING' => 'Processing',
                        'COMPLETED' => 'Completed',
                        'FAILED' => 'Failed',
                        'REVERSED' => 'Reversed',
                    ]),

                Tables\Filters\SelectFilter::make('payout_type')
                    ->label('Type')
                    ->options([
                        'MANUAL' => 'Manual',
                        'STANDARD' => 'Standard',
                        'INSTANT' => 'Instant',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for payouts
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No payout requests')
            ->emptyStateDescription('Request your first payout to withdraw your earnings.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Request Payout')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payout Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('gross_amount')
                            ->label('Gross Amount')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('payout_fee')
                            ->label('Fee')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('net_amount')
                            ->label('Net Amount')
                            ->money('NGN')
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Bank Account')
                    ->schema([
                        Infolists\Components\TextEntry::make('merchantAccount.bank_name')
                            ->label('Bank'),
                        Infolists\Components\TextEntry::make('merchantAccount.account_number')
                            ->label('Account Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('merchantAccount.account_name')
                            ->label('Account Name'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Status & Tracking')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'PENDING' => 'warning',
                                'PROCESSING' => 'info',
                                'COMPLETED' => 'success',
                                'FAILED' => 'danger',
                                'REVERSED' => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('payout_type')
                            ->label('Type')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Requested At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('initiated_at')
                            ->label('Initiated At')
                            ->dateTime()
                            ->placeholder('Not yet'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->dateTime()
                            ->placeholder('Not yet'),
                        Infolists\Components\TextEntry::make('failure_reason')
                            ->label('Failure Reason')
                            ->placeholder('N/A')
                            ->visible(fn ($record) => $record->status === 'FAILED'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Provider Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider')
                            ->label('Payment Provider')
                            ->placeholder('Not assigned'),
                        Infolists\Components\TextEntry::make('provider_reference')
                            ->label('Provider Reference')
                            ->copyable()
                            ->placeholder('Not assigned'),
                        Infolists\Components\TextEntry::make('provider_transfer_code')
                            ->label('Transfer Code')
                            ->copyable()
                            ->placeholder('Not assigned'),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->columns(3),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
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
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        // Payouts cannot be edited once created
        return false;
    }
}
