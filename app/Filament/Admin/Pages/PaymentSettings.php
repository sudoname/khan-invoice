<?php

namespace App\Filament\Admin\Pages;

use App\Models\PaymentSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.admin.pages.payment-settings';

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?string $title = 'Payment Settings';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 98;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'paystack_fee_percentage' => PaymentSetting::get('paystack_fee_percentage', '1.5'),
            'paystack_fee_minimum' => PaymentSetting::get('paystack_fee_minimum', '100'),
            'paystack_fee_cap' => PaymentSetting::get('paystack_fee_cap', '3000'),
            'service_charge_percentage' => PaymentSetting::get('service_charge_percentage', '2'),
            'service_charge_minimum' => PaymentSetting::get('service_charge_minimum', '150'),
            'service_charge_cap' => PaymentSetting::get('service_charge_cap', '3000'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Paystack Fee Settings')
                    ->description('Configure Paystack processing fees. Formula: (Percentage + Fixed Amount), capped at maximum. Paystack\'s actual local fee: 1.5% + ₦100, capped at ₦2,000')
                    ->schema([
                        TextInput::make('paystack_fee_percentage')
                            ->label('Paystack Fee Percentage (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->suffix('%')
                            ->helperText('Percentage of invoice amount (Paystack local: 1.5%)'),

                        TextInput::make('paystack_fee_minimum')
                            ->label('Fixed Amount (₦)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('₦')
                            ->helperText('Fixed amount ADDED to percentage (Paystack: ₦100)'),

                        TextInput::make('paystack_fee_cap')
                            ->label('Maximum Fee Cap (₦)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('₦')
                            ->helperText('Maximum total fee cap (Paystack local: ₦2,000)'),
                    ])
                    ->columns(3),

                Section::make('Service Charge Settings')
                    ->description('Configure service charge for all payments')
                    ->schema([
                        TextInput::make('service_charge_percentage')
                            ->label('Service Charge Percentage (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->suffix('%')
                            ->helperText('Percentage service charge on invoice amount'),

                        TextInput::make('service_charge_minimum')
                            ->label('Minimum Service Charge (₦)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('₦')
                            ->helperText('Minimum charge if percentage is lower than this amount'),

                        TextInput::make('service_charge_cap')
                            ->label('Maximum Service Charge Cap (₦)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('₦')
                            ->helperText('Maximum charge cap - fees won\'t exceed this amount'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            PaymentSetting::set($key, $value);
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
