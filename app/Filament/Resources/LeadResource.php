<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\WhatsApp\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('product_interest')
                            ->label('Product/Service Interest')
                            ->maxLength(255),

                        Forms\Components\Select::make('stage')
                            ->options([
                                'new' => 'New',
                                'qualified' => 'Qualified',
                                'invoiced' => 'Invoiced',
                                'paid' => 'Paid',
                                'lost' => 'Lost',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('score')
                            ->label('Lead Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(50)
                            ->helperText('Score from 0-100 based on engagement'),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('WhatsApp Details')
                    ->schema([
                        Forms\Components\TextInput::make('contact.name')
                            ->label('WhatsApp Contact')
                            ->disabled(),

                        Forms\Components\TextInput::make('contact.phone_e164')
                            ->label('Phone Number')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->product_interest),

                Tables\Columns\TextColumn::make('contact.phone_e164')
                    ->label('Phone')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('stage')
                    ->colors([
                        'secondary' => 'new',
                        'info' => 'qualified',
                        'warning' => 'invoiced',
                        'success' => 'paid',
                        'danger' => 'lost',
                    ]),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->default('Unassigned'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'new' => 'New',
                        'qualified' => 'Qualified',
                        'invoiced' => 'Invoiced',
                        'paid' => 'Paid',
                        'lost' => 'Lost',
                    ]),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('high_score')
                    ->label('High Score (≥80)')
                    ->query(fn (Builder $query) => $query->where('score', '>=', 80)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('qualify')
                        ->label('Mark Qualified')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->qualify())
                        ->visible(fn ($record) => $record->stage === 'new'),

                    Tables\Actions\Action::make('mark_lost')
                        ->label('Mark Lost')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->updateStage('lost'))
                        ->visible(fn ($record) => !in_array($record->stage, ['paid', 'lost'])),

                    Tables\Actions\Action::make('view_conversation')
                        ->label('View Conversation')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn ($record) => $record->conversation ? WaConversationResource::getUrl('view', ['record' => $record->conversation]) : null)
                        ->visible(fn ($record) => $record->wa_conversation_id),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign')
                        ->label('Assign To')
                        ->icon('heroicon-o-user')
                        ->form([
                            Forms\Components\Select::make('assigned_to')
                                ->label('Assign To')
                                ->relationship('assignedUser', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['assigned_to' => $data['assigned_to']]);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['contact', 'assignedUser']);
    }
}
