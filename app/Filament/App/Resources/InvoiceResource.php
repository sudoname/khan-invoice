<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\InvoiceResource\Pages;
use App\Filament\App\Resources\InvoiceResource\RelationManagers;
use App\Models\BrandKit;
use App\Models\Invoice;
use App\Models\MarketingTemplate;
use App\Services\AI\MarketingDesignService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $navigationGroup = 'Get Paid';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Create, send, and track invoices';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Simple Mode Toggle - Makes invoicing quick and easy
                Forms\Components\Section::make('Invoice Mode')
                    ->description('Choose between simple or advanced invoice mode')
                    ->schema([
                        Forms\Components\Toggle::make('simple_mode')
                            ->label('Simple Mode')
                            ->helperText('Hide tax, discounts, and advanced options for quick invoicing')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    // Simple mode: Set tax to 0
                                    $set('vat_rate', 0);
                                    $set('wht_rate', 0);
                                    $set('discount_total', 0);
                                } else {
                                    // Advanced mode: Restore default VAT
                                    $set('vat_rate', 7.5);
                                }
                            })
                            ->inline(false),
                    ])
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->collapsible()
                    ->collapsed(false),

                // Step 1: Customer Selection (Most Important First)
                Forms\Components\Section::make('Who is this invoice for?')
                    ->description('Select or add a new customer')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(auth()->id()),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('Customer Name'),
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company (Optional)'),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->label('Email'),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number'),
                            ])
                            ->createOptionModalHeading('Add New Customer')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->persistCollapsed(),

                // Step 2: Business Profile (Optional - Auto-created if not selected)
                Forms\Components\Section::make('Which business is invoicing?')
                    ->description('Optional: Select or create your business profile (you can add this later)')
                    ->schema([
                        Forms\Components\Select::make('business_profile_id')
                            ->label('Business Profile')
                            ->relationship('businessProfile', 'business_name', fn ($query) => $query->where('user_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->placeholder('Skip for now (you can add later)')
                            ->helperText('Leave blank to invoice without business branding. You can add your business details anytime.')
                            ->createOptionForm([
                                Forms\Components\Hidden::make('user_id')
                                    ->default(auth()->id()),

                                Forms\Components\Section::make('Business Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('business_name')
                                            ->required()
                                            ->label('Business Name')
                                            ->placeholder('e.g., Khan Innovations Nigeria Limited')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->label('Business Email')
                                            ->placeholder('contact@yourbusiness.com'),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Business Phone')
                                            ->tel()
                                            ->placeholder('+234 XXX XXX XXXX'),
                                        Forms\Components\FileUpload::make('logo_url')
                                            ->label('Logo (Optional)')
                                            ->image()
                                            ->directory('business-logos')
                                            ->maxSize(2048)
                                            ->helperText('Max 2MB, JPG or PNG'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Address')
                                    ->schema([
                                        Forms\Components\TextInput::make('address_line1')
                                            ->label('Street Address')
                                            ->placeholder('123 Business Street'),
                                        Forms\Components\TextInput::make('city')
                                            ->label('City')
                                            ->placeholder('Lagos'),
                                        Forms\Components\TextInput::make('state')
                                            ->label('State')
                                            ->placeholder('Lagos'),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Banking Details (Optional)')
                                    ->description('For receiving payments')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Bank Name')
                                            ->placeholder('e.g., Providus Bank'),
                                        Forms\Components\TextInput::make('bank_account_name')
                                            ->label('Account Name')
                                            ->placeholder('Khan Innovations Nigeria Limited'),
                                        Forms\Components\TextInput::make('bank_account_number')
                                            ->label('Account Number')
                                            ->placeholder('1234567890')
                                            ->numeric()
                                            ->maxLength(10),
                                    ])
                                    ->columns(3)
                                    ->collapsed()
                                    ->collapsible(),

                                Forms\Components\Section::make('Tax Information (Optional)')
                                    ->schema([
                                        Forms\Components\TextInput::make('cac_number')
                                            ->label('CAC Registration Number')
                                            ->placeholder('RC XXXXXX'),
                                        Forms\Components\TextInput::make('tin')
                                            ->label('Tax Identification Number (TIN)')
                                            ->placeholder('XXXXXXXXXX-XXXX'),
                                    ])
                                    ->columns(2)
                                    ->collapsed()
                                    ->collapsible(),
                            ])
                            ->createOptionModalHeading('Create Business Profile')
                            ->createOptionAction(function ($action) {
                                return $action
                                    ->label('+ Create Business Profile')
                                    ->modalWidth('3xl')
                                    ->icon('heroicon-o-plus-circle')
                                    ->modalSubmitActionLabel('Create Profile')
                                    ->modalDescription('Add your business details to appear on invoices');
                            })
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed(),

                // Step 3: Invoice Details
                Forms\Components\Section::make('Invoice Details')
                    ->description('Basic invoice information')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice Number')
                            ->required()
                            ->default(fn () => \App\Models\Invoice::generateInvoiceNumber())
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated sequential number'),
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->required()
                            ->default(now()->addDays(30))
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'partially_paid' => 'Partially Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set, $record) {
                                // When status changes to 'paid', automatically set amount_paid to total_amount
                                if ($state === 'paid') {
                                    // Get total from record if editing, otherwise try from form state
                                    $totalAmount = $record?->total_amount ?? $get('total_amount') ?? 0;
                                    $set('amount_paid', $totalAmount);
                                }
                            }),
                        Forms\Components\Select::make('currency')
                            ->label('Currency')
                            ->options(function () {
                                return \App\Models\Currency::where('is_active', true)
                                    ->pluck('name', 'code')
                                    ->toArray();
                            })
                            ->required()
                            ->default('NGN')
                            ->searchable()
                            ->helperText('Select invoice currency - 53+ currencies supported'),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->collapsible()
                    ->persistCollapsed(),

                // Step 4: Line Items (Core Content)
                Forms\Components\Section::make('What are you charging for?')
                    ->description('Add products or services to this invoice')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->placeholder('e.g., Website Development')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Price')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₦'),
                                Forms\Components\TextInput::make('discount')
                                    ->label('Discount')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('₦'),
                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Total')
                                    ->content(function (Get $get) {
                                        $quantity = floatval($get('quantity') ?: 0);
                                        $price = floatval($get('unit_price') ?: 0);
                                        $discount = floatval($get('discount') ?: 0);

                                        // Line total WITHOUT per-item tax (VAT is applied at invoice level)
                                        $total = ($quantity * $price) - $discount;

                                        return '₦' . number_format($total, 2);
                                    }),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->addActionLabel('Add Line Item')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-shopping-cart'),

                // Step 5: Tax & Totals (Advanced - Collapsed by Default, Hidden in Simple Mode)
                Forms\Components\Section::make('Tax & Discounts')
                    ->description('Optional: Add invoice-level tax and discounts')
                    ->schema([
                        Forms\Components\TextInput::make('discount_total')
                            ->label('Invoice Discount')
                            ->numeric()
                            ->default(0)
                            ->prefix('₦')
                            ->helperText('Additional discount on entire invoice'),
                        Forms\Components\TextInput::make('vat_rate')
                            ->label('VAT Rate (%)')
                            ->required()
                            ->numeric()
                            ->default(7.5)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Nigeria standard VAT rate is 7.5%'),
                        Forms\Components\TextInput::make('wht_rate')
                            ->label('Withholding Tax Rate (%)')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('WHT will be deducted from total'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Already Paid')
                            ->numeric()
                            ->default(0)
                            ->prefix('₦')
                            ->helperText('If customer made partial payment'),
                    ])
                    ->icon('heroicon-o-calculator')
                    ->columns(4)
                    ->collapsed()
                    ->collapsible()
                    ->persistCollapsed()
                    ->hidden(fn (Get $get): bool => $get('simple_mode') === true),

                // Step 6: Additional Information (Optional - Collapsed by Default)
                Forms\Components\Section::make('Additional Information')
                    ->description('Optional: Add notes and invoice footer')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Private Notes')
                            ->helperText('Internal notes (not shown on invoice)')
                            ->placeholder('Add internal notes about this invoice...')
                            ->rows(3),
                        Forms\Components\Textarea::make('footer')
                            ->label('Invoice Footer')
                            ->helperText('Text shown at bottom of invoice (e.g., payment terms)')
                            ->placeholder('Thank you for your business...')
                            ->rows(3),
                    ])
                    ->icon('heroicon-o-document-plus')
                    ->columns(2)
                    ->collapsed()
                    ->collapsible()
                    ->persistCollapsed(),

                // Step 7: Payment Settings (Phase 3)
                Forms\Components\Section::make('Online Payment Settings')
                    ->description('Control how customers can pay this invoice online')
                    ->schema([
                        Forms\Components\Toggle::make('payment_enabled')
                            ->label('Enable Online Payments')
                            ->default(true)
                            ->live()
                            ->helperText('Allow customers to pay via Paystack "Pay Now" button')
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('payment_expires_at')
                            ->label('Payment Link Expires At')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('After this date, online payment will be disabled. Leave empty for no expiration.')
                            ->visible(fn (Get $get) => $get('payment_enabled') === true)
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-credit-card')
                    ->collapsed()
                    ->collapsible()
                    ->persistCollapsed(),

                // Fee Notice
                Forms\Components\Placeholder::make('fee_notice')
                    ->label('')
                    ->content(new \Illuminate\Support\HtmlString('
                        <div class="text-sm text-gray-500 text-center py-2">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Online payments via "Pay Now" button include processing fees.
                            <a href="/fees" target="_blank" class="text-blue-600 hover:text-blue-800 underline">View fee schedule</a>
                        </div>
                    ')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Issued')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Invoiced'),
                    ]),
                Tables\Columns\TextColumn::make('amount_remaining')
                    ->label('Amount Remaining')
                    ->money('NGN')
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw("(total_amount - amount_paid) {$direction}");
                    })
                    ->state(fn (Invoice $record): float => $record->total_amount - $record->amount_paid)
                    ->color(fn (Invoice $record): string => $record->total_amount - $record->amount_paid > 0 ? 'warning' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'info' => 'partially_paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('NGN')
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('NGN')
                            ->label('Total Paid'),
                    ]),
                Tables\Columns\TextColumn::make('public_id')
                    ->label('Public Link')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('Link copied!')
                    ->formatStateUsing(fn ($state) => url("/inv/{$state}")),
                Tables\Columns\IconColumn::make('payment_enabled')
                    ->label('Online Pay')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->payment_enabled ? 'Online payment enabled' : 'Online payment disabled'),
                Tables\Columns\TextColumn::make('payment_expires_at')
                    ->label('Payment Expires')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Never')
                    ->color(fn ($record) => $record->payment_expires_at && $record->payment_expires_at < now() ? 'danger' : null)
                    ->description(fn ($record) => $record->payment_expires_at && $record->payment_expires_at < now() ? 'Expired' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'partially_paid' => 'Partially Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now())->whereNotIn('status', ['paid', 'cancelled']))
                    ->label('Overdue Invoices'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_public')
                    ->label('View Public')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Invoice $record): string => url("/inv/{$record->public_id}"))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_invoice')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Invoice')
                    ->modalDescription(fn (Invoice $record) => "Send invoice {$record->invoice_number} to {$record->customer->name}?")
                    ->action(function (Invoice $record) {
                        $record->update(['status' => 'sent']);
                        // Here you can add email sending logic later
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice sent')
                            ->body('Invoice has been marked as sent.')
                    )
                    ->visible(fn (Invoice $record): bool => $record->status === 'draft'),
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->update([
                            'status' => 'paid',
                            'amount_paid' => $record->total_amount
                        ]);
                    })
                    ->visible(fn (Invoice $record): bool => in_array($record->status, ['sent', 'overdue', 'partially_paid'])),

                Tables\Actions\Action::make('share_invoice')
                    ->label('Share')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('template_id')
                            ->label('Select Template')
                            ->options(function () {
                                return MarketingTemplate::active()
                                    ->where('category', 'invoice-share')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->default(function () {
                                return MarketingTemplate::active()
                                    ->where('category', 'invoice-share')
                                    ->first()?->id;
                            })
                            ->helperText('Choose a template for your share graphic'),

                        Forms\Components\Textarea::make('message')
                            ->label('Custom Message (Optional)')
                            ->rows(3)
                            ->maxLength(300)
                            ->placeholder('Add a custom message or leave blank for auto-generated text'),
                    ])
                    ->modalHeading('Share Invoice as Graphic')
                    ->modalDescription('Generate a marketing graphic to share on WhatsApp, Instagram, or other social media')
                    ->modalWidth('md')
                    ->action(function (Invoice $record, array $data) {
                        try {
                            $marketingService = app(MarketingDesignService::class);

                            $design = $marketingService->createFromInvoice(
                                invoice: $record,
                                templateId: $data['template_id'],
                                customMessage: $data['message'] ?? null
                            );

                            Notification::make()
                                ->title('Share Graphic Created!')
                                ->body('Your invoice share graphic is being generated.')
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->button()
                                        ->url(MarketingDesignResource::getUrl('view', ['record' => $design->id])),
                                ])
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Create Share Graphic')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn () => config('marketing.enabled', true)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Admin users can see all invoices, members only see their own
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
