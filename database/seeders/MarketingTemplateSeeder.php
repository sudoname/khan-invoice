<?php

namespace Database\Seeders;

use App\Models\MarketingTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketingTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'WhatsApp Status - Invoice Paid',
                'slug' => 'whatsapp-invoice-paid',
                'category' => 'invoice-share',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 80, 'bottom' => 80, 'left' => 40, 'right' => 40],
                    'text_zones' => [
                        ['name' => 'header', 'max_chars' => 30, 'area' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 300]],
                        ['name' => 'amount', 'max_chars' => 20, 'area' => ['x' => 0, 'y' => 300, 'width' => 1080, 'height' => 400]],
                        ['name' => 'footer', 'max_chars' => 50, 'area' => ['x' => 0, 'y' => 1600, 'width' => 1080, 'height' => 240]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#10B981', 'secondary' => '#6366F1', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 40, 'element_gap' => 20],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'WhatsApp Status - Payment Reminder',
                'slug' => 'whatsapp-payment-reminder',
                'category' => 'invoice-share',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 80, 'bottom' => 80, 'left' => 40, 'right' => 40],
                    'text_zones' => [
                        ['name' => 'header', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 300]],
                        ['name' => 'details', 'max_chars' => 100, 'area' => ['x' => 0, 'y' => 300, 'width' => 1080, 'height' => 600]],
                        ['name' => 'cta', 'max_chars' => 30, 'area' => ['x' => 0, 'y' => 1600, 'width' => 1080, 'height' => 240]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#F59E0B', 'secondary' => '#EF4444', 'text' => '#1F2937'],
                    'spacing' => ['padding' => 40, 'element_gap' => 20],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Instagram Post - Product Launch',
                'slug' => 'instagram-product-launch',
                'category' => 'product',
                'aspect_ratio' => '1:1',
                'width' => 1080,
                'height' => 1080,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 12],
                    'safe_margins' => ['top' => 60, 'bottom' => 60, 'left' => 60, 'right' => 60],
                    'text_zones' => [
                        ['name' => 'headline', 'max_chars' => 35, 'area' => ['x' => 0, 'y' => 0, 'width' => 1080, 'height' => 300]],
                        ['name' => 'body', 'max_chars' => 80, 'area' => ['x' => 0, 'y' => 300, 'width' => 1080, 'height' => 500]],
                        ['name' => 'brand', 'max_chars' => 25, 'area' => ['x' => 0, 'y' => 900, 'width' => 1080, 'height' => 120]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#8B5CF6', 'secondary' => '#EC4899', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 60, 'element_gap' => 30],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Instagram Story - Feature Announcement',
                'slug' => 'instagram-feature-announcement',
                'category' => 'general',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 100, 'bottom' => 100, 'left' => 50, 'right' => 50],
                    'text_zones' => [
                        ['name' => 'title', 'max_chars' => 30, 'area' => ['x' => 0, 'y' => 200, 'width' => 1080, 'height' => 400]],
                        ['name' => 'description', 'max_chars' => 120, 'area' => ['x' => 0, 'y' => 600, 'width' => 1080, 'height' => 800]],
                        ['name' => 'cta', 'max_chars' => 20, 'area' => ['x' => 0, 'y' => 1500, 'width' => 1080, 'height' => 200]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#3B82F6', 'secondary' => '#14B8A6', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 50, 'element_gap' => 25],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Facebook Post - Testimonial',
                'slug' => 'facebook-testimonial',
                'category' => 'general',
                'aspect_ratio' => '4:5',
                'width' => 1080,
                'height' => 1350,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 14],
                    'safe_margins' => ['top' => 70, 'bottom' => 70, 'left' => 70, 'right' => 70],
                    'text_zones' => [
                        ['name' => 'quote', 'max_chars' => 150, 'area' => ['x' => 0, 'y' => 200, 'width' => 1080, 'height' => 700]],
                        ['name' => 'author', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 900, 'width' => 1080, 'height' => 200]],
                        ['name' => 'brand', 'max_chars' => 25, 'area' => ['x' => 0, 'y' => 1150, 'width' => 1080, 'height' => 150]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#0EA5E9', 'secondary' => '#6366F1', 'text' => '#1F2937'],
                    'spacing' => ['padding' => 70, 'element_gap' => 30],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Landscape - Payment Receipt',
                'slug' => 'landscape-payment-receipt',
                'category' => 'invoice-share',
                'aspect_ratio' => '16:9',
                'width' => 1920,
                'height' => 1080,
                'layout_schema' => [
                    'grid' => ['columns' => 16, 'rows' => 9],
                    'safe_margins' => ['top' => 60, 'bottom' => 60, 'left' => 80, 'right' => 80],
                    'text_zones' => [
                        ['name' => 'header', 'max_chars' => 50, 'area' => ['x' => 0, 'y' => 0, 'width' => 1920, 'height' => 300]],
                        ['name' => 'receipt_details', 'max_chars' => 200, 'area' => ['x' => 0, 'y' => 300, 'width' => 1920, 'height' => 600]],
                        ['name' => 'footer', 'max_chars' => 60, 'area' => ['x' => 0, 'y' => 900, 'width' => 1920, 'height' => 150]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#059669', 'secondary' => '#6366F1', 'text' => '#111827'],
                    'spacing' => ['padding' => 80, 'element_gap' => 40],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'WhatsApp Status - Event Promo',
                'slug' => 'whatsapp-event-promo',
                'category' => 'general',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 80, 'bottom' => 80, 'left' => 40, 'right' => 40],
                    'text_zones' => [
                        ['name' => 'event_name', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 200, 'width' => 1080, 'height' => 350]],
                        ['name' => 'details', 'max_chars' => 100, 'area' => ['x' => 0, 'y' => 550, 'width' => 1080, 'height' => 600]],
                        ['name' => 'date_location', 'max_chars' => 60, 'area' => ['x' => 0, 'y' => 1200, 'width' => 1080, 'height' => 400]],
                        ['name' => 'cta', 'max_chars' => 25, 'area' => ['x' => 0, 'y' => 1650, 'width' => 1080, 'height' => 200]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#DC2626', 'secondary' => '#F59E0B', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 40, 'element_gap' => 20],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Square - Promotional Offer',
                'slug' => 'square-promotional-offer',
                'category' => 'product',
                'aspect_ratio' => '1:1',
                'width' => 1080,
                'height' => 1080,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 12],
                    'safe_margins' => ['top' => 60, 'bottom' => 60, 'left' => 60, 'right' => 60],
                    'text_zones' => [
                        ['name' => 'discount', 'max_chars' => 15, 'area' => ['x' => 0, 'y' => 150, 'width' => 1080, 'height' => 300]],
                        ['name' => 'offer_details', 'max_chars' => 80, 'area' => ['x' => 0, 'y' => 450, 'width' => 1080, 'height' => 400]],
                        ['name' => 'validity', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 850, 'width' => 1080, 'height' => 150]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#F59E0B', 'secondary' => '#EF4444', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 60, 'element_gap' => 30],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'WhatsApp Status - Service Showcase',
                'slug' => 'whatsapp-service-showcase',
                'category' => 'product',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 80, 'bottom' => 80, 'left' => 40, 'right' => 40],
                    'text_zones' => [
                        ['name' => 'service_name', 'max_chars' => 35, 'area' => ['x' => 0, 'y' => 200, 'width' => 1080, 'height' => 300]],
                        ['name' => 'benefits', 'max_chars' => 120, 'area' => ['x' => 0, 'y' => 500, 'width' => 1080, 'height' => 700]],
                        ['name' => 'pricing', 'max_chars' => 30, 'area' => ['x' => 0, 'y' => 1250, 'width' => 1080, 'height' => 300]],
                        ['name' => 'contact', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 1600, 'width' => 1080, 'height' => 250]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#8B5CF6', 'secondary' => '#14B8A6', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 40, 'element_gap' => 20],
                ],
                'is_active' => true,
                'is_premium' => false,
                'usage_count' => 0,
            ],

            [
                'name' => 'Instagram Story - Business Update',
                'slug' => 'instagram-business-update',
                'category' => 'general',
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'layout_schema' => [
                    'grid' => ['columns' => 12, 'rows' => 16],
                    'safe_margins' => ['top' => 100, 'bottom' => 100, 'left' => 50, 'right' => 50],
                    'text_zones' => [
                        ['name' => 'headline', 'max_chars' => 40, 'area' => ['x' => 0, 'y' => 250, 'width' => 1080, 'height' => 400]],
                        ['name' => 'update_text', 'max_chars' => 150, 'area' => ['x' => 0, 'y' => 650, 'width' => 1080, 'height' => 900]],
                        ['name' => 'brand_tag', 'max_chars' => 30, 'area' => ['x' => 0, 'y' => 1600, 'width' => 1080, 'height' => 200]],
                    ],
                ],
                'default_styles' => [
                    'fonts' => ['heading' => 'Inter', 'body' => 'Inter'],
                    'colors' => ['primary' => '#6366F1', 'secondary' => '#EC4899', 'text' => '#FFFFFF'],
                    'spacing' => ['padding' => 50, 'element_gap' => 25],
                ],
                'is_active' => true,
                'is_premium' => true,
                'usage_count' => 0,
            ],
        ];

        foreach ($templates as $template) {
            MarketingTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('✅ Created ' . count($templates) . ' marketing templates');
    }
}
