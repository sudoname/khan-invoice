<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;

class ApiSettings extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static string $view = 'filament.app.pages.api-settings';
    protected static ?string $navigationLabel = 'API Settings';
    protected static ?string $title = 'API Configuration';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 95;

    public ?array $data = [];
    public string $newTokenName = '';
    public string $generatedToken = '';

    public function mount(): void
    {
        $this->form->fill([
            'api_enabled' => auth()->user()->api_enabled,
            'api_rate_limit' => auth()->user()->api_rate_limit ?? 60,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('API Access Control')
                    ->description('Enable or disable API access for your account')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Toggle::make('api_enabled')
                            ->label('Enable API Access')
                            ->helperText('Allow external applications to access your data via REST API')
                            ->live(),

                        TextInput::make('api_rate_limit')
                            ->label('Rate Limit (requests per minute)')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(1000)
                            ->default(60)
                            ->helperText('Maximum number of API requests allowed per minute')
                            ->disabled(fn ($get) => !$get('api_enabled'))
                            ->dehydrated(true),

                        Placeholder::make('api_last_used')
                            ->label('Last API Usage')
                            ->content(fn () => auth()->user()->api_last_used_at
                                ? auth()->user()->api_last_used_at->diffForHumans()
                                : 'Never used'),
                    ])
                    ->columns(2),

                Section::make('API Documentation')
                    ->description('Learn how to integrate with Khan Invoice API')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Placeholder::make('base_url')
                            ->label('Base URL')
                            ->content(url('/api/v1')),

                        Placeholder::make('authentication')
                            ->label('Authentication')
                            ->content('Bearer Token (Include in Authorization header)'),

                        Placeholder::make('endpoints')
                            ->label('Available Endpoints')
                            ->content(function () {
                                return view('filament.app.components.api-endpoints');
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return auth()->user()->tokens()->latest();
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Token Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('abilities')
                    ->label('Abilities')
                    ->badge()
                    ->formatStateUsing(fn ($state) => empty($state) ? 'Full Access' : implode(', ', $state)),

                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->since(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->actions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->delete();

                        Notification::make()
                            ->title('Token revoked successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No API tokens')
            ->emptyStateDescription('Create your first API token to start using the API')
            ->emptyStateIcon('heroicon-o-key');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->update([
            'api_enabled' => $data['api_enabled'] ?? false,
            'api_rate_limit' => $data['api_rate_limit'] ?? 60,
        ]);

        Notification::make()
            ->title('API settings saved successfully')
            ->success()
            ->send();
    }

    public function createToken(): void
    {
        $this->validate([
            'newTokenName' => 'required|string|max:255',
        ]);

        // Check if email is verified
        if (!auth()->user()->hasVerifiedEmail()) {
            Notification::make()
                ->title('Email verification required')
                ->body('Please verify your email address before creating API tokens')
                ->warning()
                ->send();

            return;
        }

        // Auto-enable API access when creating first token
        if (!auth()->user()->api_enabled) {
            auth()->user()->update(['api_enabled' => true]);
        }

        $token = auth()->user()->createToken($this->newTokenName);
        $this->generatedToken = $token->plainTextToken;

        Notification::make()
            ->title('API token created successfully')
            ->body('Copy the token now. You will not be able to see it again!')
            ->success()
            ->duration(10000)
            ->send();

        $this->newTokenName = '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
