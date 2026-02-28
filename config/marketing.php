<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketing Feature Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the AI marketing generator feature globally.
    |
    */
    'enabled' => env('KINVOICE_MARKETING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Claude AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Claude AI integration for design generation.
    |
    */
    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model' => env('CLAUDE_MODEL', 'claude-sonnet-4-5-20250929'),
        'max_tokens' => env('CLAUDE_MAX_TOKENS', 4096),
        'temperature' => env('CLAUDE_TEMPERATURE', 1.0),
        'timeout' => env('CLAUDE_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for image rendering engines.
    |
    | Supported engines:
    | - 'dalle3': DALL-E 3 AI image generation (ultra-realistic, best quality)
    | - 'playwright': HTML to PNG via Playwright (basic quality)
    | - 'wkhtmltoimage': HTML to PNG via wkhtmltoimage (basic quality)
    |
    */
    'rendering' => [
        'engine' => env('RENDER_ENGINE', 'dalle3'), // 'dalle3', 'playwright', or 'wkhtmltoimage'
        'timeout' => env('RENDER_TIMEOUT', 60), // seconds
        'quality' => env('RENDER_QUALITY', 90), // 1-100 for PNG compression
        'playwright_executable' => env('PLAYWRIGHT_EXECUTABLE', null),

        // DALL-E 3 settings
        'dalle3_quality' => env('DALLE3_QUALITY', 'hd'), // 'standard' or 'hd'

        // Playwright-specific settings
        'playwright' => [
            'headless' => true,
            'device_scale_factor' => 2, // For high-DPI rendering
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing generated marketing designs.
    |
    */
    'storage' => [
        'disk' => env('MARKETING_STORAGE_DISK', 'public'),
        'path' => 'marketing-designs',
        'url_ttl' => 7200, // seconds (2 hours for signed URLs)
        'cleanup_after_days' => env('MARKETING_CLEANUP_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for background rendering queue.
    |
    */
    'queue' => [
        'connection' => env('MARKETING_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('MARKETING_QUEUE_NAME', 'marketing'),
        'max_attempts' => 3,
        'retry_delay' => 60, // seconds
        'timeout' => 120, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for design generation per user tier.
    |
    */
    'rate_limits' => [
        'free' => [
            'per_hour' => 2,
            'per_day' => 5,
            'per_month' => 50,
        ],
        'basic' => [
            'per_hour' => env('KINVOICE_MARKETING_RATE_LIMIT_BASIC', 5),
            'per_day' => 20,
            'per_month' => 200,
        ],
        'pro' => [
            'per_hour' => env('KINVOICE_MARKETING_RATE_LIMIT_PRO', 10),
            'per_day' => 50,
            'per_month' => 500,
        ],
        'enterprise' => [
            'per_hour' => 50,
            'per_day' => 200,
            'per_month' => 2000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Design Constraints
    |--------------------------------------------------------------------------
    |
    | Constraints for generated designs.
    |
    */
    'constraints' => [
        'min_width' => 400,
        'max_width' => 2400,
        'min_height' => 400,
        'max_height' => 2400,
        'max_file_size' => 5 * 1024 * 1024, // 5MB
        'allowed_aspect_ratios' => ['1:1', '9:16', '16:9', '4:5'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI prompt engineering.
    |
    */
    'prompts' => [
        'system_context' => 'You are a professional graphic designer creating marketing materials for Nigerian SMEs. Your designs must be mobile-first, WhatsApp-optimized, and culturally relevant.',
        'max_prompt_length' => 500,
        'default_language' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific features within the marketing generator.
    |
    */
    'features' => [
        'multi_variant_generation' => env('MARKETING_MULTI_VARIANT', false),
        'ab_testing' => env('MARKETING_AB_TESTING', false),
        'batch_processing' => env('MARKETING_BATCH_PROCESSING', false),
        'direct_whatsapp_posting' => env('MARKETING_WHATSAPP_POST', false),
        'community_templates' => env('MARKETING_COMMUNITY_TEMPLATES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Track usage metrics for marketing designs.
    |
    */
    'analytics' => [
        'enabled' => true,
        'track_downloads' => true,
        'track_shares' => true,
        'track_template_usage' => true,
    ],
];
