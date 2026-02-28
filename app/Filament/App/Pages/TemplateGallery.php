<?php

namespace App\Filament\App\Pages;

use App\Models\MarketingTemplate;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class TemplateGallery extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string $view = 'filament.app.pages.template-gallery';
    protected static ?string $navigationLabel = 'Template Gallery';
    protected static ?string $title = 'Template Gallery';
    protected static ?string $navigationGroup = 'Marketing Tools';
    protected static ?int $navigationSort = 3;

    public ?string $selectedCategory = 'all';
    public ?string $searchQuery = '';

    /**
     * Get all template categories
     */
    public function getCategories(): Collection
    {
        return MarketingTemplate::active()
            ->select('category')
            ->distinct()
            ->get()
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'value' => $category,
                    'label' => ucwords(str_replace('-', ' ', $category)),
                    'icon' => $this->getCategoryIcon($category),
                ];
            });
    }

    /**
     * Get filtered templates based on category and search
     */
    public function getTemplates(): Collection
    {
        $query = MarketingTemplate::active()->orderBy('usage_count', 'desc');

        // Filter by category
        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        // Search by name or slug
        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('slug', 'like', '%' . $this->searchQuery . '%');
            });
        }

        return $query->get()->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'category' => $template->category,
                'aspect_ratio' => $template->aspect_ratio,
                'width' => $template->width,
                'height' => $template->height,
                'preview_url' => $template->preview_url,
                'usage_count' => $template->usage_count,
                'is_premium' => $template->is_premium,
                'description' => $this->getTemplateDescription($template),
            ];
        });
    }

    /**
     * Get icon for category
     */
    private function getCategoryIcon(string $category): string
    {
        $iconMap = [
            'whatsapp-status' => 'heroicon-o-chat-bubble-left-right',
            'instagram-post' => 'heroicon-o-photo',
            'instagram-story' => 'heroicon-o-device-phone-mobile',
            'facebook-post' => 'heroicon-o-share',
            'invoice-share' => 'heroicon-o-document-text',
            'product-promo' => 'heroicon-o-shopping-bag',
            'event' => 'heroicon-o-calendar',
            'announcement' => 'heroicon-o-megaphone',
        ];

        return $iconMap[$category] ?? 'heroicon-o-rectangle-group';
    }

    /**
     * Get template description based on aspect ratio and category
     */
    private function getTemplateDescription(MarketingTemplate $template): string
    {
        $descriptions = [
            'whatsapp-status' => 'Perfect for WhatsApp Status updates (9:16 vertical)',
            'instagram-post' => 'Square format for Instagram feed posts (1:1)',
            'instagram-story' => 'Vertical format for Instagram Stories (9:16)',
            'facebook-post' => 'Optimized for Facebook timeline posts',
            'invoice-share' => 'Share invoice details with clients on social media',
            'product-promo' => 'Promote your products and services',
            'event' => 'Announce upcoming events and webinars',
            'announcement' => 'Make important business announcements',
        ];

        return $descriptions[$template->category] ?? "Create stunning {$template->aspect_ratio} designs";
    }

    /**
     * Filter templates by category
     */
    public function filterByCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    /**
     * Search templates
     */
    public function searchTemplates(string $query): void
    {
        $this->searchQuery = $query;
    }

    /**
     * Create design from template
     */
    public function createFromTemplate(int $templateId): void
    {
        $this->redirect(route('filament.app.resources.marketing-designs.create', ['template' => $templateId]));
    }
}
