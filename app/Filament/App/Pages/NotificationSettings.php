<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static string $view = 'filament.app.pages.notification-settings';
    protected static ?string $navigationLabel = 'Notification Settings';
    protected static ?string $title = 'Notification Preferences';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(): void
    {
        $preferences = auth()->user()->notificationPreferences;

        if (!$preferences) {
            $preferences = auth()->user()->notificationPreferences()->create([]);
        }

        $this->form->fill($preferences->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('WhatsApp Notifications')
                    ->description('Configure WhatsApp Business notifications via Twilio. WhatsApp messages will be charged to your account.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Toggle::make('whatsapp_enabled')
                            ->label('Enable WhatsApp Notifications')
                            ->helperText('Master switch for all WhatsApp notifications')
                            ->live(),

                        TextInput::make('whatsapp_credits_remaining')
                            ->label('WhatsApp Credits Remaining')
                            ->disabled()
                            ->suffix('credits')
                            ->helperText('Contact support to purchase more WhatsApp credits'),

                        Toggle::make('whatsapp_payment_received')
                            ->label('Payment Received')
                            ->helperText('Notify when a customer makes a payment')
                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),

                        Toggle::make('whatsapp_invoice_sent')
                            ->label('Invoice Sent to Customer')
                            ->helperText('Notify customer when invoice is sent')
                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),

                        Toggle::make('whatsapp_payment_reminder')
                            ->label('Payment Reminders')
                            ->helperText('Send reminders before and on due date')
                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),

                        Toggle::make('whatsapp_invoice_overdue')
                            ->label('Invoice Overdue')
                            ->helperText('Notify when invoice becomes overdue')
                            ->disabled(fn ($get) => !$get('whatsapp_enabled')),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('SMS Notifications')
                    ->description('Configure SMS notifications for important events. SMS messages will be charged to your account.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->schema([
                        Toggle::make('sms_enabled')
                            ->label('Enable SMS Notifications')
                            ->helperText('Master switch for all SMS notifications')
                            ->live(),

                        TextInput::make('sms_credits_remaining')
                            ->label('SMS Credits Remaining')
                            ->disabled()
                            ->suffix('credits')
                            ->helperText('Contact support to purchase more SMS credits'),

                        Toggle::make('sms_payment_received')
                            ->label('Payment Received')
                            ->helperText('Notify when a customer makes a payment')
                            ->disabled(fn ($get) => !$get('sms_enabled')),

                        Toggle::make('sms_invoice_sent')
                            ->label('Invoice Sent to Customer')
                            ->helperText('Notify customer when invoice is sent')
                            ->disabled(fn ($get) => !$get('sms_enabled')),

                        Toggle::make('sms_payment_reminder')
                            ->label('Payment Reminders')
                            ->helperText('Send reminders before and on due date')
                            ->disabled(fn ($get) => !$get('sms_enabled')),

                        Toggle::make('sms_invoice_overdue')
                            ->label('Invoice Overdue')
                            ->helperText('Notify when invoice becomes overdue')
                            ->disabled(fn ($get) => !$get('sms_enabled')),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Email Notifications')
                    ->description('Configure email notifications. Email notifications are free.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Toggle::make('email_payment_received')
                            ->label('Payment Received')
                            ->helperText('Notify when a customer makes a payment')
                            ->default(true),

                        Toggle::make('email_invoice_sent')
                            ->label('Invoice Sent to Customer')
                            ->helperText('Notify customer when invoice is sent')
                            ->default(true),

                        Toggle::make('email_payment_reminder')
                            ->label('Payment Reminders')
                            ->helperText('Send reminders before and on due date')
                            ->default(true),

                        Toggle::make('email_invoice_overdue')
                            ->label('Invoice Overdue')
                            ->helperText('Notify when invoice becomes overdue')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->notificationPreferences()->updateOrCreate(
            ['user_id' => auth()->id()],
            $data
        );

        Notification::make()
            ->title('Notification preferences saved successfully')
            ->success()
            ->send();
    }
}
