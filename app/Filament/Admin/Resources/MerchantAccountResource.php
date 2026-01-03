<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\MerchantAccountResource\Pages;
use App\Models\Payment\MerchantAccount;
use App\Services\PaystackService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class MerchantAccountResource extends Resource
{
    protected static ?string $model = MerchantAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Merchant Accounts';

    protected static ?string $navigationGroup = 'Payment Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('verification_status', 'PENDING')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Merchant Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Merchant')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                "{$record->name} ({$record->email})"
                            ),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Bank Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('bank_code')
                            ->label('Bank Code')
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('account_number')
                            ->label('Account Number')
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('account_name')
                            ->label('Account Name')
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Select::make('account_type')
                            ->label('Account Type')
                            ->options([
                                'savings' => 'Savings Account',
                                'current' => 'Current Account (Checking)',
                                'corporate' => 'Corporate Account',
                                'domiciliary' => 'Domiciliary Account',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Verification')
                    ->schema([
                        Forms\Components\Select::make('verification_status')
                            ->label('Verification Status')
                            ->options([
                                'PENDING' => 'Pending',
                                'VERIFIED' => 'Verified',
                                'FAILED' => 'Failed',
                                'SUSPENDED' => 'Suspended',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'VERIFIED') {
                                    $set('verified_at', now());
                                }
                            }),

                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At')
                            ->disabled()
                            ->visible(fn (Forms\Get $get) => $get('verification_status') === 'VERIFIED'),

                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->rows(3)
                            ->placeholder('Add notes about verification status, issues found, etc.')
                            ->helperText('Internal notes - not visible to merchant')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Account Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true)
                            ->live()
                            ->helperText('Inactive accounts cannot receive payouts'),

                        Forms\Components\Textarea::make('deactivation_reason')
                            ->label('Deactivation Reason')
                            ->rows(2)
                            ->visible(fn (Forms\Get $get) => !$get('is_active'))
                            ->required(fn (Forms\Get $get) => !$get('is_active'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settlement Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Account')
                            ->helperText('Merchant\'s default account for payouts'),

                        Forms\Components\Select::make('settlement_schedule')
                            ->label('Settlement Schedule')
                            ->options([
                                'INSTANT' => 'Instant',
                                'DAILY' => 'Daily',
                                'WEEKLY' => 'Weekly',
                                'MANUAL' => 'Manual',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('minimum_payout')
                            ->label('Minimum Payout')
                            ->numeric()
                            ->prefix('₦')
                            ->required()
                            ->minValue(500),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->user->email),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-library'),

                Tables\Columns\TextColumn::make('account_number')
                    ->label('Account')
                    ->searchable()
                    ->copyable()
                    ->description(fn ($record) => $record->account_name . ' (' . ucfirst($record->account_type ?? 'savings') . ')'),

                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Verification')
                    ->colors([
                        'warning' => 'PENDING',
                        'success' => 'VERIFIED',
                        'danger' => 'FAILED',
                        'gray' => 'SUSPENDED',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'PENDING',
                        'heroicon-o-check-circle' => 'VERIFIED',
                        'heroicon-o-x-circle' => 'FAILED',
                        'heroicon-o-no-symbol' => 'SUSPENDED',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_balance')
                    ->label('Balance')
                    ->state(fn ($record) => '₦' . number_format($record->getAvailableBalance(), 2))
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Verification Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'VERIFIED' => 'Verified',
                        'FAILED' => 'Failed',
                        'SUSPENDED' => 'Suspended',
                    ])
                    ->default('PENDING'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->placeholder('All accounts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Account')
                    ->placeholder('All accounts')
                    ->trueLabel('Primary only')
                    ->falseLabel('Secondary only'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Bank Account')
                    ->modalDescription(fn ($record) =>
                        "Verify {$record->bank_name} account {$record->account_number} for {$record->user->name}?"
                    )
                    ->form([
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->placeholder('Add any notes about the verification process...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $paystackService = new PaystackService();

                            // First, verify the account with Paystack
                            $verificationResult = $paystackService->resolveAccountNumber(
                                $record->account_number,
                                $record->bank_code
                            );

                            if (!$verificationResult['status']) {
                                Notification::make()
                                    ->danger()
                                    ->title('Account Verification Failed')
                                    ->body("Could not verify account: {$verificationResult['message']}")
                                    ->persistent()
                                    ->send();
                                return;
                            }

                            // Check if account name matches
                            $paystackAccountName = $verificationResult['data']['account_name'] ?? '';
                            $providedAccountName = $record->account_name;

                            // Log verification for review
                            Log::info('Account verification comparison', [
                                'merchant_account_id' => $record->id,
                                'provided_name' => $providedAccountName,
                                'paystack_name' => $paystackAccountName,
                            ]);

                            // Create transfer recipient on Paystack
                            $recipientResult = $paystackService->createTransferRecipient([
                                'name' => $record->account_name,
                                'account_number' => $record->account_number,
                                'bank_code' => $record->bank_code,
                                'currency' => 'NGN',
                                'description' => "Transfer recipient for {$record->user->name} ({$record->bank_name})",
                                'metadata' => [
                                    'merchant_account_id' => $record->id,
                                    'user_id' => $record->user_id,
                                    'user_name' => $record->user->name,
                                ],
                            ]);

                            if ($recipientResult['status']) {
                                // Success - save recipient code and verify account
                                $record->update([
                                    'verification_status' => 'VERIFIED',
                                    'verified_at' => now(),
                                    'verification_notes' => $data['verification_notes'] ?? null,
                                    'provider_recipient_code' => $recipientResult['data']['recipient_code'],
                                    'provider_metadata' => $recipientResult['data'],
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Account Verified')
                                    ->body("Bank account verified and transfer recipient created successfully. Recipient Code: {$recipientResult['data']['recipient_code']}")
                                    ->send();

                                Log::info('Merchant account verified with Paystack recipient', [
                                    'merchant_account_id' => $record->id,
                                    'recipient_code' => $recipientResult['data']['recipient_code'],
                                ]);
                            } else {
                                // Failed to create recipient - still verify but show warning
                                $record->update([
                                    'verification_status' => 'VERIFIED',
                                    'verified_at' => now(),
                                    'verification_notes' => ($data['verification_notes'] ?? '') . "\n\nPaystack Recipient Creation Failed: " . $recipientResult['message'],
                                ]);

                                Notification::make()
                                    ->warning()
                                    ->title('Account Verified (with Warning)')
                                    ->body("Bank account verified but Paystack recipient creation failed: {$recipientResult['message']}. Payouts may not work until this is resolved.")
                                    ->persistent()
                                    ->send();

                                Log::error('Failed to create Paystack transfer recipient', [
                                    'merchant_account_id' => $record->id,
                                    'error' => $recipientResult['message'],
                                    'account_details' => [
                                        'bank_name' => $record->bank_name,
                                        'account_number' => $record->account_number,
                                        'bank_code' => $record->bank_code,
                                    ],
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Exception occurred - still verify but log error
                            $record->update([
                                'verification_status' => 'VERIFIED',
                                'verified_at' => now(),
                                'verification_notes' => ($data['verification_notes'] ?? '') . "\n\nException during Paystack setup: " . $e->getMessage(),
                            ]);

                            Notification::make()
                                ->danger()
                                ->title('Account Verified (with Error)')
                                ->body("Bank account verified but an error occurred: {$e->getMessage()}")
                                ->persistent()
                                ->send();

                            Log::error('Exception creating Paystack transfer recipient', [
                                'merchant_account_id' => $record->id,
                                'exception' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    })
                    ->visible(fn ($record) => $record->verification_status === 'PENDING'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Bank Account')
                    ->modalDescription(fn ($record) =>
                        "Reject {$record->bank_name} account {$record->account_number} for {$record->user->name}?"
                    )
                    ->form([
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Rejection Reason')
                            ->placeholder('Explain why this account was rejected...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'verification_status' => 'FAILED',
                            'verification_notes' => $data['verification_notes'],
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Account Rejected')
                            ->body("Bank account for {$record->user->name} has been rejected.")
                            ->send();
                    })
                    ->visible(fn ($record) => $record->verification_status === 'PENDING'),

                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Bank Account')
                    ->form([
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Suspension Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'verification_status' => 'SUSPENDED',
                            'is_active' => false,
                            'verification_notes' => $data['verification_notes'],
                            'deactivation_reason' => $data['verification_notes'],
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Account Suspended')
                            ->body("Bank account for {$record->user->name} has been suspended.")
                            ->send();
                    })
                    ->visible(fn ($record) => $record->verification_status === 'VERIFIED'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify_selected')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->verification_status === 'PENDING') {
                                    $record->update([
                                        'verification_status' => 'VERIFIED',
                                        'verified_at' => now(),
                                    ]);
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Accounts Verified')
                                ->body(count($records) . ' account(s) have been verified.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No merchant accounts')
            ->emptyStateDescription('Merchants will see their accounts here once they add bank details.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Merchant Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Merchant Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Merchant Email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('Merchant Phone')
                            ->placeholder('Not provided'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Bank Account Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('bank_name')
                            ->label('Bank Name')
                            ->icon('heroicon-o-building-library'),
                        Infolists\Components\TextEntry::make('bank_code')
                            ->label('Bank Code'),
                        Infolists\Components\TextEntry::make('account_number')
                            ->label('Account Number')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('account_name')
                            ->label('Account Name'),
                        Infolists\Components\TextEntry::make('account_type')
                            ->label('Account Type')
                            ->formatStateUsing(fn ($state) => ucfirst($state) . ' Account')
                            ->badge(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Verification Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('verification_status')
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
                        Infolists\Components\TextEntry::make('verification_notes')
                            ->label('Verification Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Account Status & Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('is_primary')
                            ->label('Primary Account')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->badge()
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                        Infolists\Components\TextEntry::make('settlement_schedule')
                            ->badge(),
                        Infolists\Components\TextEntry::make('minimum_payout')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('available_balance')
                            ->label('Available Balance')
                            ->state(fn ($record) => $record->getAvailableBalance())
                            ->money('NGN')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('deactivation_reason')
                            ->label('Deactivation Reason')
                            ->placeholder('N/A')
                            ->visible(fn ($record) => !$record->is_active)
                            ->columnSpanFull(),
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

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->collapsible(),
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
            'index' => Pages\ListMerchantAccounts::route('/'),
            'view' => Pages\ViewMerchantAccount::route('/{record}'),
            'edit' => Pages\EditMerchantAccount::route('/{record}/edit'),
        ];
    }
}
