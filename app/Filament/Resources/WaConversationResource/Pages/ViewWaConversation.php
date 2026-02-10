<?php

namespace App\Filament\Resources\WaConversationResource\Pages;

use App\Filament\Resources\WaConversationResource;
use App\Services\WhatsApp\WhatsAppService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWaConversation extends ViewRecord
{
    protected static string $resource = WaConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_message')
                ->label('Send Message')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(3)
                        ->maxLength(4096),
                ])
                ->action(function (array $data, WhatsAppService $whatsAppService) {
                    $conversation = $this->record;
                    $contact = $conversation->contact;

                    $message = $whatsAppService->sendText(
                        auth()->id(),
                        $contact->phone_e164,
                        $data['message'],
                        $conversation->id
                    );

                    if ($message) {
                        Notification::make()
                            ->title('Message sent successfully')
                            ->success()
                            ->send();

                        $this->redirect(request()->url());
                    } else {
                        Notification::make()
                            ->title('Failed to send message')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'open' => 'Open',
                            'paused' => 'Paused',
                            'handoff' => 'Handoff',
                            'closed' => 'Closed',
                        ])
                        ->required()
                        ->default(fn () => $this->record->status),
                ])
                ->action(function (array $data) {
                    $this->record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Status updated successfully')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('close')
                ->label('Close Conversation')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'closed']);

                    Notification::make()
                        ->title('Conversation closed')
                        ->success()
                        ->send();

                    return redirect(WaConversationResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status !== 'closed'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('contact.name')
                            ->label('Name'),

                        Infolists\Components\TextEntry::make('contact.phone_e164')
                            ->label('Phone Number'),

                        Infolists\Components\TextEntry::make('contact.last_seen_at')
                            ->label('Last Seen')
                            ->dateTime('M d, Y h:i A')
                            ->default('Never'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Conversation Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'open' => 'success',
                                'paused' => 'warning',
                                'handoff' => 'danger',
                                'closed' => 'secondary',
                            }),

                        Infolists\Components\TextEntry::make('state')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'idle' => 'secondary',
                                'collecting_lead' => 'info',
                                'collecting_invoice' => 'warning',
                                'invoice_sent' => 'success',
                                'awaiting_payment' => 'primary',
                                'handoff' => 'danger',
                            }),

                        Infolists\Components\IconEntry::make('human_handoff')
                            ->label('Requires Handoff')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('handoff_reason')
                            ->label('Handoff Reason')
                            ->visible(fn ($record) => $record->human_handoff),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Conversation Context')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('context')
                            ->label('Collected Data')
                            ->hiddenLabel(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->context)),

                Infolists\Components\Section::make('Messages')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('messages')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('direction')
                                            ->badge()
                                            ->color(fn ($state) => $state === 'inbound' ? 'info' : 'success'),

                                        Infolists\Components\TextEntry::make('created_at')
                                            ->dateTime('M d, Y h:i A'),

                                        Infolists\Components\TextEntry::make('body')
                                            ->label('Message')
                                            ->columnSpan(2)
                                            ->markdown(),

                                        Infolists\Components\TextEntry::make('status')
                                            ->badge()
                                            ->visible(fn ($record) => $record->direction === 'outbound')
                                            ->color(fn ($state) => match ($state) {
                                                'sent', 'delivered', 'read' => 'success',
                                                'queued' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary',
                                            }),
                                    ]),
                            ])
                            ->contained(false),
                    ]),
            ]);
    }
}
