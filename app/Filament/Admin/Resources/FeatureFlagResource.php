<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FeatureFlagResource\Pages;
use App\Models\FeatureFlag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static ?string $navigationGroup = 'System Configuration';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feature Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Feature Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique identifier for the feature (e.g., instant_payouts)')
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('name')
                            ->label('Feature Name')
                            ->required()
                            ->helperText('Human-readable name for the feature'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->helperText('Detailed description of what this feature does'),

                        Forms\Components\Toggle::make('enabled')
                            ->label('Enabled')
                            ->default(false)
                            ->helperText('Toggle to enable or disable this feature globally')
                            ->live(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Environment Configuration')
                    ->description('Restrict this feature to specific environments')
                    ->schema([
                        Forms\Components\CheckboxList::make('environments')
                            ->label('Enabled Environments')
                            ->options([
                                'local' => 'Local Development',
                                'staging' => 'Staging',
                                'production' => 'Production',
                            ])
                            ->helperText('Leave empty to enable for all environments')
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('Advanced Rules')
                    ->description('JSON configuration for advanced feature rules')
                    ->schema([
                        Forms\Components\Textarea::make('rules')
                            ->label('Rules (JSON)')
                            ->rows(5)
                            ->helperText('Advanced rules in JSON format (optional)')
                            ->placeholder('{"user_ids": [1, 2, 3], "percentage": 50}')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\Placeholder::make('enabled_at')
                            ->label('Enabled At')
                            ->content(fn ($record) => $record?->enabled_at ? $record->enabled_at->format('M d, Y g:i A') : 'Not enabled yet'),

                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record) => $record?->created_at ? $record->created_at->format('M d, Y g:i A') : 'N/A'),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at ? $record->updated_at->format('M d, Y g:i A') : 'N/A'),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Feature Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('environments')
                    ->label('Environments')
                    ->badge()
                    ->separator(',')
                    ->default('All')
                    ->color('info'),

                Tables\Columns\TextColumn::make('enabled_at')
                    ->label('Enabled At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not enabled'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label('Status')
                    ->placeholder('All features')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->enabled ? 'Disable' : 'Enable')
                    ->icon(fn ($record) => $record->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->enabled ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->enabled ? 'Disable Feature' : 'Enable Feature')
                    ->modalDescription(fn ($record) => $record->enabled
                        ? "Are you sure you want to disable '{$record->name}'? This will immediately affect all users."
                        : "Are you sure you want to enable '{$record->name}'? This will immediately make the feature available."
                    )
                    ->action(function ($record) {
                        if ($record->enabled) {
                            $record->disable();
                            Notification::make()
                                ->success()
                                ->title('Feature Disabled')
                                ->body("'{$record->name}' has been disabled.")
                                ->send();
                        } else {
                            $record->enable();
                            Notification::make()
                                ->success()
                                ->title('Feature Enabled')
                                ->body("'{$record->name}' has been enabled.")
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enable')
                    ->label('Enable Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->enable();
                        }

                        Notification::make()
                            ->success()
                            ->title('Features Enabled')
                            ->body(count($records) . ' feature(s) have been enabled.')
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('disable')
                    ->label('Disable Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->disable();
                        }

                        Notification::make()
                            ->success()
                            ->title('Features Disabled')
                            ->body(count($records) . ' feature(s) have been disabled.')
                            ->send();
                    }),

                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('key');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Feature Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('key')
                            ->label('Feature Key')
                            ->copyable()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Feature Name'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),

                        Infolists\Components\IconEntry::make('enabled')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Environment Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('environments')
                            ->label('Enabled Environments')
                            ->badge()
                            ->separator(',')
                            ->default('All environments'),
                    ]),

                Infolists\Components\Section::make('Advanced Rules')
                    ->schema([
                        Infolists\Components\TextEntry::make('rules')
                            ->label('Rules')
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'No rules configured')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('enabled_at')
                            ->label('Enabled At')
                            ->dateTime()
                            ->placeholder('Not enabled'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'view' => Pages\ViewFeatureFlag::route('/{record}'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
