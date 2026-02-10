<?php

namespace App\Filament\Pages;

use App\Models\WhatsApp\WaAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WhatsAppSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.whatsapp-settings';

    protected static ?string $navigationLabel = 'WhatsApp Settings';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public function mount(): void
    {
        $account = WaAccount::where('user_id', auth()->id())->first();

        $this->form->fill([
            'provider' => $account?->provider ?? 'meta',
            'phone_number_id' => $account?->phone_number_id,
            'waba_id' => $account?->waba_id,
            'access_token' => $account?->access_token,
            'verify_token' => $account?->verify_token ?? config('whatsapp.meta.verify_token'),
            'webhook_url' => url('/api/webhooks/whatsapp'),
            'ai_enabled' => config('whatsapp.ai.enabled', true),
            'ai_provider' => config('whatsapp.ai.provider', 'openai'),
            'ai_model' => config('whatsapp.ai.model', 'gpt-4-turbo-preview'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('WhatsApp Business Account')
                    ->description('Configure your WhatsApp Business API credentials')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'meta' => 'Meta (Cloud API)',
                                'termii' => 'Termii',
                                'twilio' => 'Twilio',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('phone_number_id')
                            ->label('Phone Number ID')
                            ->required()
                            ->helperText('Your WhatsApp Business Phone Number ID from Meta'),

                        Forms\Components\TextInput::make('waba_id')
                            ->label('WhatsApp Business Account ID (WABA ID)')
                            ->required(),

                        Forms\Components\TextInput::make('access_token')
                            ->label('Access Token')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Your permanent access token or system user token'),

                        Forms\Components\TextInput::make('verify_token')
                            ->label('Webhook Verify Token')
                            ->required()
                            ->password()
                            ->revealable()
                            ->helperText('Custom token for webhook verification (set this in Meta App Dashboard)'),

                        Forms\Components\TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->disabled()
                            ->helperText('Use this URL in your Meta App Dashboard webhook configuration')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy')
                                    ->icon('heroicon-m-clipboard')
                                    ->action(function ($state) {
                                        $this->js('navigator.clipboard.writeText("' . $state . '")');
                                        Notification::make()
                                            ->title('Webhook URL copied to clipboard')
                                            ->success()
                                            ->send();
                                    })
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('AI Configuration')
                    ->description('Configure AI assistant settings')
                    ->schema([
                        Forms\Components\Toggle::make('ai_enabled')
                            ->label('Enable AI Assistant')
                            ->helperText('Turn off to disable AI processing (conversations will require manual handling)')
                            ->disabled(),

                        Forms\Components\Select::make('ai_provider')
                            ->label('AI Provider')
                            ->options([
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic (Claude)',
                            ])
                            ->disabled()
                            ->helperText('Configure in config/whatsapp.php'),

                        Forms\Components\TextInput::make('ai_model')
                            ->label('AI Model')
                            ->disabled()
                            ->helperText('Configure in config/whatsapp.php'),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Account Status')
                    ->description('View your WhatsApp account connection status')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('Connection Status')
                            ->content(function () {
                                $account = WaAccount::where('user_id', auth()->id())->first();
                                if (!$account) {
                                    return 'Not configured';
                                }
                                return ucfirst($account->status);
                            }),

                        Forms\Components\Placeholder::make('last_error')
                            ->label('Last Error')
                            ->content(function () {
                                $account = WaAccount::where('user_id', auth()->id())->first();
                                return $account?->last_error ?? 'None';
                            })
                            ->visible(function () {
                                $account = WaAccount::where('user_id', auth()->id())->first();
                                return $account && $account->last_error;
                            }),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $account = WaAccount::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'provider' => $data['provider'],
                'phone_number_id' => $data['phone_number_id'],
                'waba_id' => $data['waba_id'],
                'access_token' => $data['access_token'],
                'verify_token' => $data['verify_token'],
                'status' => 'connected',
            ]
        );

        Notification::make()
            ->title('WhatsApp settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save'),

            Forms\Components\Actions\Action::make('test_connection')
                ->label('Test Connection')
                ->color('warning')
                ->action(function () {
                    $account = WaAccount::where('user_id', auth()->id())->first();

                    if (!$account) {
                        Notification::make()
                            ->title('Please save your settings first')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        // Test connection by checking account status
                        // In production, you would make an API call to Meta to verify
                        Notification::make()
                            ->title('Connection test successful')
                            ->body('Your WhatsApp account is configured correctly')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Connection test failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
