<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BusinessProfileResource\Pages;
use App\Filament\App\Resources\BusinessProfileResource\RelationManagers;
use App\Models\BusinessProfile;
use App\Helpers\NigerianBanks;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BusinessProfileResource extends Resource
{
    protected static ?string $model = BusinessProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Business Profiles';

    protected static ?string $modelLabel = 'Business Profile';

    protected static ?string $navigationGroup = 'Setup';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cac_number')
                            ->label('CAC Number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tin')
                            ->label('TIN (Tax Identification Number)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address_line1')
                            ->label('Address Line 1')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('address_line2')
                            ->label('Address Line 2')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('state')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('postal_code')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\Select::make('bank_name')
                            ->label('Bank')
                            ->options(NigerianBanks::options())
                            ->searchable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->maxLength(10)
                            ->minLength(10)
                            ->numeric(),
                        Forms\Components\Select::make('bank_account_type')
                            ->label('Account Type')
                            ->options([
                                'savings' => 'Savings',
                                'current' => 'Current',
                                'domiciliary' => 'Domiciliary',
                            ]),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Invoice Defaults')
                    ->schema([
                        Forms\Components\TextInput::make('default_currency')
                            ->label('Default Currency')
                            ->required()
                            ->default('NGN')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('default_vat_rate')
                            ->label('Default VAT Rate (%)')
                            ->required()
                            ->numeric()
                            ->default(7.5)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Business Logo')
                            ->image()
                            ->maxSize(2048)
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio(null)
                            ->imageResizeTargetWidth('500')
                            ->imageResizeTargetHeight('500')
                            ->optimize('webp')
                            ->helperText('Recommended: PNG or JPG, max 2MB. Will be optimized automatically.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cac_number')
                    ->label('CAC Number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tin')
                    ->label('TIN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('default_currency')
                    ->label('Currency')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('default_vat_rate')
                    ->label('VAT Rate')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Admin users can see all business profiles, members only see their own
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
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
            'index' => Pages\ListBusinessProfiles::route('/'),
            'create' => Pages\CreateBusinessProfile::route('/create'),
            'edit' => Pages\EditBusinessProfile::route('/{record}/edit'),
        ];
    }
}
