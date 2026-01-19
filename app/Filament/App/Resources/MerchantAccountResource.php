<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MerchantAccountResource\Pages;
use App\Models\Payment\MerchantAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class MerchantAccountResource extends Resource
{
    protected static ?string $model = MerchantAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Bank Accounts';

    protected static ?string $navigationGroup = 'Get Paid';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bank Account Details')
                    ->description('Enter your bank account information for receiving payouts')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\Select::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->searchable()
                            ->options([
                                'Access Bank' => 'Access Bank',
                                'Citibank' => 'Citibank',
                                'Ecobank Nigeria' => 'Ecobank Nigeria',
                                'Fidelity Bank' => 'Fidelity Bank',
                                'First Bank of Nigeria' => 'First Bank of Nigeria',
                                'First City Monument Bank' => 'First City Monument Bank',
                                'Guaranty Trust Bank' => 'Guaranty Trust Bank',
                                'Heritage Bank' => 'Heritage Bank',
                                'Keystone Bank' => 'Keystone Bank',
                                'Polaris Bank' => 'Polaris Bank',
                                'Providus Bank' => 'Providus Bank',
                                'Stanbic IBTC Bank' => 'Stanbic IBTC Bank',
                                'Standard Chartered' => 'Standard Chartered',
                                'Sterling Bank' => 'Sterling Bank',
                                'Union Bank of Nigeria' => 'Union Bank of Nigeria',
                                'United Bank for Africa' => 'United Bank for Africa',
                                'Unity Bank' => 'Unity Bank',
                                'Wema Bank' => 'Wema Bank',
                                'Zenith Bank' => 'Zenith Bank',
                                'Jaiz Bank' => 'Jaiz Bank',
                                'Kuda Bank' => 'Kuda Bank',
                                'Opay' => 'Opay',
                                'PalmPay' => 'PalmPay',
                                'Moniepoint' => 'Moniepoint',
                                'VFD Microfinance Bank' => 'VFD Microfinance Bank',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-fill bank code based on selected bank
                                $bankCodes = [
                                    'Access Bank' => '044',
                                    'Citibank' => '023',
                                    'Ecobank Nigeria' => '050',
                                    'Fidelity Bank' => '070',
                                    'First Bank of Nigeria' => '011',
                                    'First City Monument Bank' => '214',
                                    'Guaranty Trust Bank' => '058',
                                    'Heritage Bank' => '030',
                                    'Keystone Bank' => '082',
                                    'Polaris Bank' => '076',
                                    'Providus Bank' => '101',
                                    'Stanbic IBTC Bank' => '221',
                                    'Standard Chartered' => '068',
                                    'Sterling Bank' => '232',
                                    'Union Bank of Nigeria' => '032',
                                    'United Bank for Africa' => '033',
                                    'Unity Bank' => '215',
                                    'Wema Bank' => '035',
                                    'Zenith Bank' => '057',
                                    'Jaiz Bank' => '301',
                                    'Kuda Bank' => '090267',
                                    'Opay' => '999992',
                                    'PalmPay' => '999991',
                                    'Moniepoint' => '50515',
                                    'VFD Microfinance Bank' => '090110',
                                ];

                                $set('bank_code', $bankCodes[$state] ?? '');
                            })
                            ->helperText('Select your bank - code will be filled automatically'),

                        Forms\Components\Hidden::make('bank_code')
                            ->required(),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Account Number')
                            ->required()
                            ->numeric()
                            ->maxLength(20)
                            ->helperText('Your 10-digit account number')
                            ->placeholder('0123456789')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                // Auto-verify account when account number is filled
                                if (strlen($state) === 10 && $get('bank_code')) {
                                    try {
                                        $paystackService = new \App\Services\PaystackService();
                                        $result = $paystackService->resolveAccountNumber($state, $get('bank_code'));

                                        if ($result['status']) {
                                            $accountName = $result['data']['account_name'];
                                            $set('account_name', $accountName);
                                            \Filament\Notifications\Notification::make()
                                                ->success()
                                                ->title('Account Verified')
                                                ->body("Account belongs to: {$accountName}")
                                                ->send();
                                        }
                                    } catch (\Exception $e) {
                                        // Silent fail - user can still enter manually
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('account_name')
                            ->label('Account Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Account name will be auto-filled after entering account number')
                            ->placeholder('Will be verified automatically'),

                        Forms\Components\Select::make('account_type')
                            ->label('Account Type')
                            ->options([
                                'savings' => 'Savings Account',
                                'current' => 'Current Account (Checking)',
                                'corporate' => 'Corporate Account',
                                'domiciliary' => 'Domiciliary Account',
                            ])
                            ->default('savings')
                            ->required()
                            ->helperText('Select your account type'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payout Preferences')
                    ->description('Configure how and when you receive payouts')
                    ->schema([
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Set as Primary Account')
                            ->default(true)
                            ->helperText('Make this your default account for receiving payouts')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('settlement_schedule')
                            ->label('Settlement Schedule')
                            ->options([
                                'INSTANT' => 'Instant (2% fee)',
                                'DAILY' => 'Daily (T+1)',
                                'WEEKLY' => 'Weekly (Every Monday)',
                                'MANUAL' => 'Manual (Request when needed)',
                            ])
                            ->default('MANUAL')
                            ->required()
                            ->helperText('How often should we send payouts to this account?'),

                        Forms\Components\TextInput::make('minimum_payout')
                            ->label('Minimum Payout Amount')
                            ->required()
                            ->numeric()
                            ->default(1000)
                            ->minValue(500)
                            ->prefix('₦')
                            ->helperText('Minimum balance required before auto-payout (₦500 - ₦100,000)'),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Placeholder::make('verification_status')
                            ->label('Verification Status')
                            ->content(fn ($record) => $record ? match($record->verification_status) {
                                'PENDING' => '⏳ Pending Verification',
                                'VERIFIED' => '✅ Verified',
                                'FAILED' => '❌ Verification Failed',
                                'SUSPENDED' => '🚫 Suspended',
                                default => 'Unknown'
                            } : '⏳ Pending'),

                        Forms\Components\Placeholder::make('verified_at')
                            ->label('Verified At')
                            ->content(fn ($record) => $record?->verified_at ? $record->verified_at->format('M d, Y g:i A') : 'Not verified yet')
                            ->visible(fn ($record) => $record?->verified_at),

                        Forms\Components\Placeholder::make('payout_balance')
                            ->label('Payout Balance')
                            ->content(fn ($record) => $record ? '₦' . number_format($record->getAvailableBalance(), 2) : '₦0.00')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('pending_payouts')
                            ->label('Pending Payouts')
                            ->content(fn ($record) => $record ? '₦' . number_format($record->getPendingPayoutAmount(), 2) : '₦0.00')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('available_balance')
                            ->label('Available Balance')
                            ->content(fn ($record) => $record ? '₦' . number_format($record->getAvailableBalanceAfterPending(), 2) : '₦0.00')
                            ->visible(fn ($record) => $record),

                        Forms\Components\Placeholder::make('completed_payouts')
                            ->label('Total Paid Out')
                            ->content(fn ($record) => $record ? '₦' . number_format($record->getCompletedPayoutAmount(), 2) : '₦0.00')
                            ->visible(fn ($record) => $record),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-library'),

                Tables\Columns\TextColumn::make('account_number')
                    ->label('Account Number')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Account number copied!'),

                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable()
                    ->limit(30)
                    ->description(fn ($record) => ucfirst($record->account_type ?? 'savings') . ' account'),

                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'VERIFIED' => 'success',
                        'FAILED' => 'danger',
                        'SUSPENDED' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'PENDING' => 'heroicon-o-clock',
                        'VERIFIED' => 'heroicon-o-check-circle',
                        'FAILED' => 'heroicon-o-x-circle',
                        'SUSPENDED' => 'heroicon-o-no-symbol',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->is_primary ? 'Primary account' : 'Secondary account'),

                Tables\Columns\TextColumn::make('payout_balance')
                    ->label('Payout Balance')
                    ->state(fn ($record) => '₦' . number_format($record->getAvailableBalance(), 2))
                    ->weight('bold')
                    ->color('success')
                    ->description('Current balance'),

                Tables\Columns\TextColumn::make('pending_payouts')
                    ->label('Pending Payouts')
                    ->state(fn ($record) => '₦' . number_format($record->getPendingPayoutAmount(), 2))
                    ->color('warning')
                    ->description('In process'),

                Tables\Columns\TextColumn::make('available_after_pending')
                    ->label('Available Balance')
                    ->state(fn ($record) => '₦' . number_format($record->getAvailableBalanceAfterPending(), 2))
                    ->weight('bold')
                    ->color(fn ($record) => $record->getAvailableBalanceAfterPending() >= 0 ? 'success' : 'danger')
                    ->description('After pending'),

                Tables\Columns\TextColumn::make('completed_payouts')
                    ->label('Total Paid Out')
                    ->state(fn ($record) => '₦' . number_format($record->getCompletedPayoutAmount(), 2))
                    ->color('gray')
                    ->description('Lifetime')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('settlement_schedule')
                    ->label('Schedule')
                    ->badge()
                    ->colors([
                        'success' => 'INSTANT',
                        'info' => 'DAILY',
                        'warning' => 'WEEKLY',
                        'gray' => 'MANUAL',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(strtolower($state))),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Verification')
                    ->options([
                        'PENDING' => 'Pending',
                        'VERIFIED' => 'Verified',
                        'FAILED' => 'Failed',
                        'SUSPENDED' => 'Suspended',
                    ]),

                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Account')
                    ->placeholder('All accounts')
                    ->trueLabel('Primary only')
                    ->falseLabel('Secondary only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('set_primary')
                    ->label('Make Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Remove primary from all other accounts
                        MerchantAccount::where('user_id', auth()->id())
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);

                        // Set this as primary
                        $record->update(['is_primary' => true]);
                    })
                    ->visible(fn ($record) => !$record->is_primary),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No bank accounts')
            ->emptyStateDescription('Add your first bank account to start receiving payouts.')
            ->emptyStateIcon('heroicon-o-building-library')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Bank Account')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Bank Account Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('bank_name')
                            ->label('Bank Name')
                            ->icon('heroicon-o-building-library'),
                        Infolists\Components\TextEntry::make('account_number')
                            ->label('Account Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('account_name')
                            ->label('Account Name'),
                        Infolists\Components\TextEntry::make('bank_code')
                            ->label('Bank Code'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Verification & Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('verification_status')
                            ->label('Verification Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'PENDING' => 'warning',
                                'VERIFIED' => 'success',
                                'FAILED' => 'danger',
                                'SUSPENDED' => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('Not verified'),
                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Account Status')
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Deactivated')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('is_primary')
                            ->label('Primary Account')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Payout Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('settlement_schedule')
                            ->label('Settlement Schedule')
                            ->badge(),
                        Infolists\Components\TextEntry::make('minimum_payout')
                            ->label('Minimum Payout')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('available_balance')
                            ->label('Available Balance')
                            ->state(fn ($record) => $record->getAvailableBalance())
                            ->money('NGN')
                            ->color('success'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Provider Integration')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider_recipient_code')
                            ->label('Recipient Code')
                            ->placeholder('Not linked')
                            ->copyable(),
                    ])
                    ->collapsed()
                    ->collapsible(),
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
            'index' => Pages\ListMerchantAccounts::route('/'),
            'create' => Pages\CreateMerchantAccount::route('/create'),
            'view' => Pages\ViewMerchantAccount::route('/{record}'),
            'edit' => Pages\EditMerchantAccount::route('/{record}/edit'),
        ];
    }
}
