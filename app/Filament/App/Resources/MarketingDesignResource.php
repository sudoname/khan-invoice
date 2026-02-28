<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MarketingDesignResource\Pages;
use App\Models\MarketingDesign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;

class MarketingDesignResource extends Resource
{
    protected static ?string $model = MarketingDesign::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'My Designs';

    protected static ?string $navigationGroup = 'Marketing Tools';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'AI-generated marketing designs';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Design Information')
                    ->description('View design details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->disabled(),

                        Forms\Components\Textarea::make('prompt')
                            ->label('Original Prompt')
                            ->rows(3)
                            ->disabled(),

                        Forms\Components\Select::make('template_id')
                            ->label('Template')
                            ->relationship('template', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('brand_kit_id')
                            ->label('Brand Kit')
                            ->relationship('brandKit', 'name')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rendering Details')
                    ->schema([
                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->disabled(),

                        Forms\Components\TextInput::make('width')
                            ->label('Width (px)')
                            ->disabled(),

                        Forms\Components\TextInput::make('height')
                            ->label('Height (px)')
                            ->disabled(),

                        Forms\Components\TextInput::make('file_size')
                            ->label('File Size')
                            ->formatStateUsing(fn ($state) => $state ? round($state / 1024, 2) . ' KB' : 'N/A')
                            ->disabled(),

                        Forms\Components\TextInput::make('download_count')
                            ->label('Downloads')
                            ->disabled(),

                        Forms\Components\TextInput::make('render_attempts')
                            ->label('Render Attempts')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('rendered_url')
                    ->label('Preview')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-design.png'))
                    ->size(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (MarketingDesign $record): string =>
                        str($record->prompt)->limit(60)
                    ),

                Tables\Columns\TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'rendering' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('width')
                    ->label('Size')
                    ->formatStateUsing(fn (MarketingDesign $record): string =>
                        $record->width && $record->height ? "{$record->width}x{$record->height}" : 'N/A'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('File')
                    ->formatStateUsing(fn ($state): string => $state ? round($state / 1024, 2) . ' KB' : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('download_count')
                    ->label('Downloads')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'rendering' => 'Rendering',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name'),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (MarketingDesign $record) => $record->isCompleted())
                    ->url(fn (MarketingDesign $record) => $record->rendered_url)
                    ->openUrlInNewTab()
                    ->after(fn (MarketingDesign $record) => $record->incrementDownloadCount()),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (MarketingDesign $record) => view(
                        'filament.pages.view-design',
                        ['design' => $record]
                    ))
                    ->modalWidth('5xl')
                    ->slideOver(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No designs yet')
            ->emptyStateDescription('Create your first AI-generated marketing design')
            ->emptyStateIcon('heroicon-o-sparkles')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Create Design')
                    ->url(fn (): string => MarketingDesignResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
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
            'index' => Pages\ListMarketingDesigns::route('/'),
            'create' => Pages\CreateMarketingDesign::route('/create'),
            'view' => Pages\ViewMarketingDesign::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
