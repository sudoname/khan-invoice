<?php

namespace App\Filament\App\Resources\MarketingDesignResource\Pages;

use App\Filament\App\Resources\MarketingDesignResource;
use App\Models\BrandKit;
use App\Models\MarketingTemplate;
use App\Services\AI\MarketingDesignService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;

class CreateMarketingDesign extends Page
{
    protected static string $resource = MarketingDesignResource::class;

    protected static string $view = 'filament.pages.create-marketing-design';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Choose Template')
                        ->description('Select a template for your design')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Forms\Components\Select::make('template_id')
                                ->label('Template')
                                ->options(function () {
                                    return MarketingTemplate::active()
                                        ->get()
                                        ->groupBy('category')
                                        ->map(function ($templates, $category) {
                                            return $templates->mapWithKeys(function ($template) {
                                                return [
                                                    $template->id => $template->name . ' (' . $template->aspect_ratio . ')'
                                                ];
                                            })->toArray();
                                        })
                                        ->mapWithKeys(function ($templates, $category) {
                                            return [
                                                ucwords(str_replace('-', ' ', $category)) => $templates
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->helperText('Choose the format and style for your design'),
                        ]),

                    Forms\Components\Wizard\Step::make('Brand Kit')
                        ->description('Choose your brand colors and fonts')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            Forms\Components\Select::make('brand_kit_id')
                                ->label('Brand Kit')
                                ->options(function () {
                                    return BrandKit::where('user_id', auth()->id())
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->placeholder('Use template defaults')
                                ->helperText('Optional: Apply your brand colors and fonts'),
                        ]),

                    Forms\Components\Wizard\Step::make('Design Prompt')
                        ->description('Describe what you want to create')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Forms\Components\Textarea::make('prompt')
                                ->label('What do you want to say?')
                                ->required()
                                ->rows(5)
                                ->maxLength(500)
                                ->placeholder('Example: Celebrate our ₦500,000 payment received from Acme Corp! Make it professional with celebration emojis.')
                                ->helperText('Be specific about the message, tone, and key details you want to include.'),

                            Forms\Components\Toggle::make('queue_rendering')
                                ->label('Queue for Background Rendering')
                                ->helperText('If enabled, the design will be processed in the background. You\'ll be notified when it\'s ready.')
                                ->default(true)
                                ->inline(false),
                        ]),
                ])
                    ->submitAction(new \Filament\Forms\Components\Actions\Action('submit'))
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        try {
            $data = $this->form->getState();

            $marketingService = app(MarketingDesignService::class);

            $design = $marketingService->createDesign(
                user: auth()->user(),
                prompt: $data['prompt'],
                templateId: $data['template_id'],
                brandKitId: $data['brand_kit_id'] ?? null,
                invoiceId: null,
                queueRendering: $data['queue_rendering'] ?? true
            );

            Notification::make()
                ->title('Design Created Successfully!')
                ->body($data['queue_rendering']
                    ? 'Your design is being generated. Check back in a few moments.'
                    : 'Your design has been generated.')
                ->success()
                ->send();

            $this->redirect(MarketingDesignResource::getUrl('view', ['record' => $design->id]));

        } catch (\Exception $e) {
            Log::error('Failed to create marketing design', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            Notification::make()
                ->title('Failed to Create Design')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
