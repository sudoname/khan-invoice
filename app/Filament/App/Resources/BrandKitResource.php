<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BrandKitResource\Pages;
use App\Models\BrandKit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandKitResource extends Resource
{
    protected static ?string $model = BrandKit::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Brand Kits';

    protected static ?string $navigationGroup = 'Marketing Tools';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Manage your brand colors and fonts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Brand Information')
                    ->description('Define your brand identity')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\TextInput::make('name')
                            ->label('Brand Kit Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Primary Brand, Holiday Theme'),

                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->directory('brand-logos')
                            ->maxSize(2048)
                            ->helperText('Upload your brand logo (Max 2MB)')
                            ->imageEditor()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Set as Default')
                            ->helperText('This brand kit will be automatically selected for new designs')
                            ->inline(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Brand Colors')
                    ->description('Choose your brand color palette')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->placeholder('#6366F1')
                            ->helperText('Main brand color'),

                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label('Secondary Color')
                            ->placeholder('#8B5CF6')
                            ->helperText('Supporting color'),

                        Forms\Components\ColorPicker::make('accent_color')
                            ->label('Accent Color')
                            ->placeholder('#F59E0B')
                            ->helperText('Highlight color'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Typography')
                    ->description('Select fonts for your brand')
                    ->schema([
                        Forms\Components\Select::make('font_heading')
                            ->label('Heading Font')
                            ->options([
                                'Inter' => 'Inter',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Lato' => 'Lato',
                                'Montserrat' => 'Montserrat',
                                'Poppins' => 'Poppins',
                                'Raleway' => 'Raleway',
                            ])
                            ->default('Inter')
                            ->required(),

                        Forms\Components\Select::make('font_body')
                            ->label('Body Font')
                            ->options([
                                'Inter' => 'Inter',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Lato' => 'Lato',
                                'Montserrat' => 'Montserrat',
                                'Poppins' => 'Poppins',
                                'Raleway' => 'Raleway',
                            ])
                            ->default('Inter')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-logo.png'))
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Primary')
                    ->sortable(),

                Tables\Columns\ColorColumn::make('secondary_color')
                    ->label('Secondary')
                    ->sortable(),

                Tables\Columns\ColorColumn::make('accent_color')
                    ->label('Accent')
                    ->sortable(),

                Tables\Columns\TextColumn::make('font_heading')
                    ->label('Heading Font')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_default')
                    ->query(fn (Builder $query): Builder => $query->where('is_default', true))
                    ->label('Default Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No brand kits yet')
            ->emptyStateDescription('Create your first brand kit to customize your designs')
            ->emptyStateIcon('heroicon-o-paint-brush')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Brand Kit')
                    ->icon('heroicon-m-plus'),
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
            'index' => Pages\ListBrandKits::route('/'),
            'create' => Pages\CreateBrandKit::route('/create'),
            'edit' => Pages\EditBrandKit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }
}
