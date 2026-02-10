<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaConversationResource\Pages;
use App\Models\WhatsApp\WaConversation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WaConversationResource extends Resource
{
    protected static ?string $model = WaConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Conversation Details')
                    ->schema([
                        Forms\Components\TextInput::make('contact.name')
                            ->label('Contact Name')
                            ->disabled(),

                        Forms\Components\TextInput::make('contact.phone_e164')
                            ->label('Phone Number')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'paused' => 'Paused',
                                'handoff' => 'Handoff',
                                'closed' => 'Closed',
                            ])
                            ->required(),

                        Forms\Components\Select::make('state')
                            ->options([
                                'idle' => 'Idle',
                                'collecting_lead' => 'Collecting Lead',
                                'collecting_invoice' => 'Collecting Invoice',
                                'invoice_sent' => 'Invoice Sent',
                                'awaiting_payment' => 'Awaiting Payment',
                                'handoff' => 'Handoff',
                            ])
                            ->disabled(),

                        Forms\Components\Toggle::make('human_handoff')
                            ->label('Human Handoff Required')
                            ->disabled(),

                        Forms\Components\Textarea::make('handoff_reason')
                            ->label('Handoff Reason')
                            ->disabled()
                            ->visible(fn ($record) => $record?->human_handoff),

                        Forms\Components\KeyValue::make('context')
                            ->label('Conversation Context')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->contact?->phone_e164),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'warning' => 'paused',
                        'danger' => 'handoff',
                        'secondary' => 'closed',
                    ]),

                Tables\Columns\BadgeColumn::make('state')
                    ->colors([
                        'secondary' => 'idle',
                        'info' => 'collecting_lead',
                        'warning' => 'collecting_invoice',
                        'success' => 'invoice_sent',
                        'primary' => 'awaiting_payment',
                        'danger' => 'handoff',
                    ]),

                Tables\Columns\IconColumn::make('human_handoff')
                    ->label('Handoff')
                    ->boolean()
                    ->trueIcon('heroicon-o-hand-raised')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->updated_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'paused' => 'Paused',
                        'handoff' => 'Handoff',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'idle' => 'Idle',
                        'collecting_lead' => 'Collecting Lead',
                        'collecting_invoice' => 'Collecting Invoice',
                        'invoice_sent' => 'Invoice Sent',
                        'awaiting_payment' => 'Awaiting Payment',
                        'handoff' => 'Handoff',
                    ]),

                Tables\Filters\TernaryFilter::make('human_handoff')
                    ->label('Requires Handoff')
                    ->placeholder('All conversations')
                    ->trueLabel('Requires handoff')
                    ->falseLabel('No handoff needed'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Chat'),

                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'closed']))
                    ->visible(fn ($record) => $record->status !== 'closed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('close')
                        ->label('Close Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'closed'])),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListWaConversations::route('/'),
            'view' => Pages\ViewWaConversation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['contact', 'messages']);
    }
}
