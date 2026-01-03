<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ReminderResource\Pages;
use App\Models\Reminder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Payment Reminders';

    protected static ?string $navigationGroup = 'Get Paid';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('invoice', function ($query) {
            $query->where('user_id', auth()->id());
        })->where('status', 'SCHEDULED')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reminder Details')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->label('Invoice')
                            ->relationship(
                                'invoice',
                                'invoice_number',
                                fn (Builder $query) => $query
                                    ->where('user_id', auth()->id())
                                    ->with(['customer'])
                                    ->whereNotIn('status', ['paid', 'cancelled'])
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                "{$record->invoice_number} - {$record->customer->name} (Due: {$record->due_date->format('M d, Y')})"
                            )
                            ->helperText('Select an unpaid invoice to remind about'),

                        Forms\Components\Select::make('reminder_type')
                            ->label('Reminder Type')
                            ->options([
                                'BEFORE_DUE' => 'Before Due Date',
                                'ON_DUE' => 'On Due Date',
                                'AFTER_DUE' => 'After Due Date',
                                'OVERDUE' => 'Overdue',
                            ])
                            ->required()
                            ->default('BEFORE_DUE')
                            ->live()
                            ->helperText('When should this reminder be sent?'),

                        Forms\Components\TextInput::make('days_offset')
                            ->label('Days Offset')
                            ->required()
                            ->numeric()
                            ->default(fn (Forms\Get $get) => $get('reminder_type') === 'BEFORE_DUE' ? -3 : 0)
                            ->helperText(fn (Forms\Get $get) => match($get('reminder_type')) {
                                'BEFORE_DUE' => 'Negative number (e.g., -3 = 3 days before due date)',
                                'AFTER_DUE', 'OVERDUE' => 'Positive number (e.g., 3 = 3 days after due date)',
                                'ON_DUE' => 'Usually 0 (on the due date)',
                                default => 'Days relative to due date',
                            }),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Schedule For')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('M d, Y - h:i A')
                            ->format('Y-m-d H:i:s')
                            ->minDate(now())
                            ->timezone('Africa/Lagos')
                            ->helperText('Exact date and time to send this reminder (Year, Month, Day, Hour:Minute AM/PM)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Delivery Channels')
                    ->description('Choose how to send this reminder')
                    ->schema([
                        Forms\Components\Toggle::make('send_email')
                            ->label('Send via Email')
                            ->default(true)
                            ->helperText('Send reminder to customer email'),

                        Forms\Components\Toggle::make('send_sms')
                            ->label('Send via SMS')
                            ->default(false)
                            ->helperText('Send SMS reminder (additional charges may apply)'),

                        Forms\Components\Toggle::make('send_whatsapp')
                            ->label('Send via WhatsApp')
                            ->default(true)
                            ->helperText('Send reminder via WhatsApp'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Message Customization')
                    ->description('Optional: Customize the reminder message')
                    ->schema([
                        Forms\Components\Textarea::make('custom_message')
                            ->label('Custom Message')
                            ->rows(4)
                            ->placeholder('Leave empty to use default reminder template...')
                            ->helperText('Add a personal note to this reminder'),

                        Forms\Components\Toggle::make('include_payment_link')
                            ->label('Include Payment Link')
                            ->default(true)
                            ->helperText('Add "Pay Now" button to reminder'),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.app.resources.invoices.view', $record->invoice_id))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('invoice.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('reminder_type')
                    ->label('Type')
                    ->colors([
                        'info' => 'BEFORE_DUE',
                        'warning' => 'ON_DUE',
                        'danger' => 'AFTER_DUE',
                        'danger' => 'OVERDUE',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'BEFORE_DUE' => 'Before Due',
                        'ON_DUE' => 'On Due Date',
                        'AFTER_DUE' => 'After Due',
                        'OVERDUE' => 'Overdue',
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->scheduled_at->diffForHumans()),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'SCHEDULED',
                        'success' => 'SENT',
                        'danger' => 'FAILED',
                        'gray' => 'CANCELLED',
                        'secondary' => 'SKIPPED',
                    ]),

                Tables\Columns\TextColumn::make('channels')
                    ->label('Channels')
                    ->state(fn ($record) => collect([
                        $record->send_email ? '📧 Email' : null,
                        $record->send_sms ? '📱 SMS' : null,
                        $record->send_whatsapp ? '💬 WhatsApp' : null,
                    ])->filter()->implode(', '))
                    ->html(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not sent'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'SCHEDULED' => 'Scheduled',
                        'SENT' => 'Sent',
                        'FAILED' => 'Failed',
                        'CANCELLED' => 'Cancelled',
                        'SKIPPED' => 'Skipped',
                    ]),

                Tables\Filters\SelectFilter::make('reminder_type')
                    ->label('Type')
                    ->options([
                        'BEFORE_DUE' => 'Before Due',
                        'ON_DUE' => 'On Due Date',
                        'AFTER_DUE' => 'After Due',
                        'OVERDUE' => 'Overdue',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'SCHEDULED'),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'CANCELLED']))
                    ->visible(fn ($record) => $record->status === 'SCHEDULED'),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->emptyStateHeading('No reminders scheduled')
            ->emptyStateDescription('Create your first payment reminder to automatically follow up on unpaid invoices.')
            ->emptyStateIcon('heroicon-o-bell-alert');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('invoice', function ($query) {
                $query->where('user_id', auth()->id());
            });
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
            'index' => Pages\ListReminders::route('/'),
            'create' => Pages\CreateReminder::route('/create'),
            'view' => Pages\ViewReminder::route('/{record}'),
            'edit' => Pages\EditReminder::route('/{record}/edit'),
        ];
    }
}
