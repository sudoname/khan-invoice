<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payment\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payout Management';

    protected static ?string $navigationGroup = 'Payment Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::whereIn('status', ['PENDING', 'PROCESSING'])->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.name')
                            ->label('Merchant')
                            ->disabled(),
                        Forms\Components\TextInput::make('gross_amount')
                            ->label('Gross Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('payout_fee')
                            ->label('Payout Fee')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\TextInput::make('net_amount')
                            ->label('Net Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'PENDING' => 'Pending',
                                'PROCESSING' => 'Processing',
                                'COMPLETED' => 'Completed',
                                'FAILED' => 'Failed',
                                'REVERSED' => 'Reversed',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Admin Actions')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('merchantAccount.account_number')
                    ->label('Account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gross_amount')
                    ->label('Gross')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_fee')
                    ->label('Fee')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('payout_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'STANDARD',
                        'warning' => 'INSTANT',
                        'secondary' => 'MANUAL',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'PENDING',
                        'info' => 'PROCESSING',
                        'success' => 'COMPLETED',
                        'danger' => 'FAILED',
                        'secondary' => 'REVERSED',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
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
                        'STANDARD' => 'Standard (Free)',
                        'INSTANT' => 'Instant (2%)',
                        'MANUAL' => 'Manual',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Process')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payout')
                    ->modalDescription('This will approve and process the payout immediately.')
                    ->action(function ($record) {
                        try {
                            $payoutService = new \App\Services\Payment\PayoutService();
                            $result = $payoutService->approvePayout($record->id, auth()->id());

                            if ($result['status']) {
                                Notification::make()
                                    ->success()
                                    ->title('Payout Approved')
                                    ->body('Payout has been approved and is being processed.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Approval Failed')
                                    ->body($result['message'])
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status === 'PENDING' && $record->requires_approval),
                Tables\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reversal Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $payoutService = new \App\Services\Payment\PayoutService();
                            $result = $payoutService->cancelPayout($record->id, $data['reason']);

                            if ($result['status']) {
                                Notification::make()
                                    ->success()
                                    ->title('Payout Reversed')
                                    ->body('Payout has been reversed successfully.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Reversal Failed')
                                    ->body($result['message'])
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => in_array($record->status, ['COMPLETED', 'PROCESSING'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payout Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Merchant'),
                        Infolists\Components\TextEntry::make('merchantAccount.bank_name')
                            ->label('Bank'),
                        Infolists\Components\TextEntry::make('merchantAccount.account_number')
                            ->label('Account Number'),
                        Infolists\Components\TextEntry::make('merchantAccount.account_name')
                            ->label('Account Name'),
                        Infolists\Components\TextEntry::make('gross_amount')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('payout_fee')
                            ->money('NGN'),
                        Infolists\Components\TextEntry::make('net_amount')
                            ->money('NGN')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('payout_type')
                            ->badge()
                            ->colors([
                                'success' => 'STANDARD',
                                'warning' => 'INSTANT',
                                'secondary' => 'MANUAL',
                            ]),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->colors([
                                'warning' => 'PENDING',
                                'info' => 'PROCESSING',
                                'success' => 'COMPLETED',
                                'danger' => 'FAILED',
                                'secondary' => 'REVERSED',
                            ]),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Provider Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider'),
                        Infolists\Components\TextEntry::make('provider_reference')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('provider_transfer_code')
                            ->copyable(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('initiated_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->dateTime()
                            ->placeholder('Not completed'),
                        Infolists\Components\TextEntry::make('failed_at')
                            ->dateTime()
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Failure Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('failure_reason')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->status === 'FAILED'),

                Infolists\Components\Section::make('Provider Response')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider_response')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }
}
