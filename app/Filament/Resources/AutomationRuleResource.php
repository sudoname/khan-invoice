<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationRuleResource\Pages;
use App\Models\WhatsApp\AutomationRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AutomationRuleResource extends Resource
{
    protected static ?string $model = AutomationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Automation Rules';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Rule Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., "Unpaid Invoice Reminder - 3 Day Schedule"'),

                        Forms\Components\Select::make('type')
                            ->label('Rule Type')
                            ->options([
                                'unpaid_invoice_followup' => 'Unpaid Invoice Follow-up',
                                'abandoned_cart_followup' => 'Abandoned Cart Follow-up',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable or disable this rule'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Schedule Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('schedule')
                            ->label('Follow-up Schedule')
                            ->addActionLabel('Add Attempt')
                            ->keyLabel('Attempt')
                            ->valueLabel('Minutes After Invoice Sent')
                            ->helperText('Define when follow-ups should be sent. Example: 60 (1 hour), 1440 (24 hours), 4320 (3 days)')
                            ->default([
                                '1' => '60',     // 1 hour
                                '2' => '1440',   // 24 hours
                                '3' => '4320',   // 3 days
                            ]),

                        Forms\Components\KeyValue::make('constraints')
                            ->label('Constraints')
                            ->addActionLabel('Add Constraint')
                            ->keyLabel('Constraint Type')
                            ->valueLabel('Value')
                            ->helperText('Optional: business_hours_start=9, business_hours_end=18')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Message Template')
                    ->schema([
                        Forms\Components\Textarea::make('message_template')
                            ->label('Message Template')
                            ->required()
                            ->rows(6)
                            ->helperText('Use variables: {{invoice_number}}, {{amount}}, {{currency}}, {{due_date}}, {{days_overdue}}, {{customer_name}}, {{business_name}}, {{payment_link}}, {{attempt_number}}')
                            ->default("Hi {{customer_name}},\n\nThis is a friendly reminder about Invoice {{invoice_number}} for {{currency}} {{amount}}.\n\nDue date: {{due_date}}\nDays overdue: {{days_overdue}}\n\nPlease make payment here: {{payment_link}}\n\nThank you!\n{{business_name}}")
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview')
                            ->content(fn ($get) => str_replace(
                                ['{{invoice_number}}', '{{amount}}', '{{currency}}', '{{due_date}}', '{{days_overdue}}', '{{customer_name}}', '{{business_name}}', '{{payment_link}}', '{{attempt_number}}'],
                                ['INV-2024-00000123', '50,000.00', 'NGN', 'Jan 15, 2024', '5', 'John Doe', 'Your Business', 'https://example.com/invoice/xyz', '1'],
                                $get('message_template') ?? ''
                            ))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rule Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => ucwords(str_replace('_', ' ', $record->type))),

                Tables\Columns\IconColumn::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('schedule_attempts')
                    ->label('Attempts')
                    ->getStateUsing(fn ($record) => count($record->getScheduleAttempts()))
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'unpaid_invoice_followup' => 'Unpaid Invoice Follow-up',
                        'abandoned_cart_followup' => 'Abandoned Cart Follow-up',
                    ]),

                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('All rules')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->active ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->active ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn ($record) => $record->active ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['active' => !$record->active])),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function ($record) {
                            $newRule = $record->replicate();
                            $newRule->name = $record->name . ' (Copy)';
                            $newRule->active = false;
                            $newRule->save();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['active' => true])),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['active' => false])),

                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAutomationRules::route('/'),
            'create' => Pages\CreateAutomationRule::route('/create'),
            'edit' => Pages\EditAutomationRule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
