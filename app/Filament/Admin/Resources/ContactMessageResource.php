<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ContactMessageResource\Pages;
use App\Filament\Admin\Resources\ContactMessageResource\RelationManagers;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->maxLength(255)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Message')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->rows(5)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status & Notes')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'New',
                                'read' => 'Read',
                                'replied' => 'Replied',
                                'archived' => 'Archived',
                            ])
                            ->required()
                            ->default('new'),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes (Internal)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Add internal notes about this message...'),
                    ])->columns(1),

                Forms\Components\Section::make('Reply Information')
                    ->schema([
                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Reply Sent')
                            ->rows(4)
                            ->disabled()
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record && $record->admin_reply),
                        Forms\Components\TextInput::make('repliedBy.name')
                            ->label('Replied By')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->replied_by),
                        Forms\Components\TextInput::make('replied_at')
                            ->label('Replied At')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->replied_at),
                        Forms\Components\TextInput::make('resolvedBy.name')
                            ->label('Resolved By')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->resolved_by),
                        Forms\Components\TextInput::make('resolved_at')
                            ->label('Resolved At')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->resolved_at),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && ($record->admin_reply || $record->resolved_at)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger',
                        'read' => 'warning',
                        'replied' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('resolved_at')
                    ->label('Resolved')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'read' => 'Read',
                        'replied' => 'Replied',
                        'archived' => 'Archived',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Reply via Email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->form([
                        Forms\Components\Textarea::make('reply')
                            ->label('Your Reply')
                            ->required()
                            ->rows(5)
                            ->placeholder('Type your reply here...'),
                    ])
                    ->action(function (ContactMessage $record, array $data) {
                        // Send email
                        \Mail::to($record->email)->send(new \App\Mail\ContactMessageReply($record, $data['reply']));

                        // Update record
                        $record->update([
                            'status' => 'replied',
                            'admin_reply' => $data['reply'],
                            'replied_by' => auth()->id(),
                            'replied_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Reply Sent')
                            ->body('Your reply has been sent to ' . $record->email)
                            ->send();
                    })
                    ->visible(fn (ContactMessage $record) => !$record->isResolved()),
                Tables\Actions\Action::make('markAsResolved')
                    ->label('Mark as Resolved')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Resolved')
                    ->modalDescription('This will mark the message as resolved. You can still view it later.')
                    ->action(function (ContactMessage $record) {
                        $record->update([
                            'status' => 'archived',
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Marked as Resolved')
                            ->send();
                    })
                    ->visible(fn (ContactMessage $record) => !$record->isResolved()),
                Tables\Actions\Action::make('markAsRead')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->visible(fn (ContactMessage $record) => $record->status === 'new')
                    ->action(fn (ContactMessage $record) => $record->update(['status' => 'read'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsRead')
                        ->label('Mark as Read')
                        ->icon('heroicon-o-eye')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['status' => 'read'])),
                    Tables\Actions\BulkAction::make('markAsReplied')
                        ->label('Mark as Replied')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['status' => 'replied'])),
                    Tables\Actions\BulkAction::make('archive')
                        ->label('Archive')
                        ->icon('heroicon-o-archive-box')
                        ->color('secondary')
                        ->action(fn ($records) => $records->each->update(['status' => 'archived'])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
