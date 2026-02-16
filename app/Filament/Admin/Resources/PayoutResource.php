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
                    ->formatStateUsing(fn ($state) => '₦' . number_format((float) $state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payout_fee')
                    ->label('Fee')
                    ->formatStateUsing(fn ($state) => '₦' . number_format((float) $state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->formatStateUsing(fn ($state) => '₦' . number_format((float) $state, 2))
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('payout_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'STANDARD' => 'success',
                        'INSTANT' => 'warning',
                        'MANUAL' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'PROCESSING' => 'info',
                        'COMPLETED' => 'success',
                        'FAILED' => 'danger',
                        'REVERSED' => 'gray',
                        default => 'gray',
                    }),
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
                            $payoutService = app(\App\Services\Payment\PayoutService::class);
                            $result = $payoutService->approvePayout($record->id, auth()->id());

                            if ($result['success']) {
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
                Tables\Actions\Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Retry Failed Payout')
                    ->modalDescription('This will refund the failed payout amount and create a new payout with the same details.')
                    ->action(function ($record) {
                        try {
                            $payoutService = app(\App\Services\Payment\PayoutService::class);
                            $result = $payoutService->retryPayout($record->id);

                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Payout Retried')
                                    ->body('A new payout has been created and is being processed.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Retry Failed')
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
                    ->visible(fn ($record) => $record->status === 'FAILED'),
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
                            $payoutService = app(\App\Services\Payment\PayoutService::class);
                            $result = $payoutService->cancelPayout($record->id, $data['reason']);

                            if ($result['success']) {
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
                            ->label('Merchant')
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        Infolists\Components\TextEntry::make('merchantAccount.bank_name')
                            ->label('Bank')
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        Infolists\Components\TextEntry::make('merchantAccount.account_number')
                            ->label('Account Number')
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        Infolists\Components\TextEntry::make('merchantAccount.account_name')
                            ->label('Account Name')
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        Infolists\Components\TextEntry::make('gross_amount')
                            ->formatStateUsing(fn ($state) => '₦' . number_format((float)($state ?? 0), 2)),
                        Infolists\Components\TextEntry::make('payout_fee')
                            ->formatStateUsing(fn ($state) => '₦' . number_format((float)($state ?? 0), 2)),
                        Infolists\Components\TextEntry::make('net_amount')
                            ->formatStateUsing(fn ($state) => '₦' . number_format((float)($state ?? 0), 2))
                            ->weight('bold')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('payout_type')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'STANDARD' => 'success',
                                'INSTANT' => 'warning',
                                'MANUAL' => 'gray',
                                default => 'gray',
                            })
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'PENDING' => 'warning',
                                'PROCESSING' => 'info',
                                'COMPLETED' => 'success',
                                'FAILED' => 'danger',
                                'REVERSED' => 'gray',
                                default => 'gray',
                            })
                            ->placeholder('N/A'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Provider Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider')
                            ->default('paystack')
                            ->formatStateUsing(fn ($state) => $state ?? 'paystack'),
                        Infolists\Components\TextEntry::make('provider_reference')
                            ->copyable()
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        Infolists\Components\TextEntry::make('provider_transfer_code')
                            ->copyable()
                            ->default('N/A')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('initiated_at')
                            ->dateTime()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y g:i A') : 'N/A'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->dateTime()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y g:i A') : 'Not completed'),
                        Infolists\Components\TextEntry::make('failed_at')
                            ->dateTime()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y g:i A') : 'N/A'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M d, Y g:i A') : 'N/A'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Failure Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('failure_reason')
                            ->columnSpanFull()
                            ->placeholder('N/A'),
                    ])
                    ->visible(fn ($record) => $record && $record->status === 'FAILED'),

                Infolists\Components\Section::make('Provider Response')
                    ->schema([
                        Infolists\Components\TextEntry::make('provider_response_display')
                            ->label('Provider Response')
                            ->formatStateUsing(fn (string $state) => '<pre>' . $state . '</pre>')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record && $record->provider_response),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'view' => Pages\ViewPayout::route('/{record}'),
        ];
    }

    /**
     * Prevent direct editing of payouts
     * Payouts must be managed through PayoutService to ensure accounting integrity
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Prevent deletion of payouts
     * Use cancel/reverse actions instead
     */
    public static function canDelete($record): bool
    {
        return false;
    }
}
